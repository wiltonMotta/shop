<template>
    <!-- 会员等级卡片（吸附轮播） -->
    <view v-if="card_list.length > 0" class="membership-card-wrap">
        <scroll-view class="membership-card-scroll" scroll-x="true"
            :scroll-left="scroll_left" :scroll-with-animation="true" :show-scrollbar="false"
            @touchstart="touch_start_event" @touchend="touch_end_event" @scroll="scroll_handle_event">
            <view class="membership-card-row">
                <view v-for="(item, index) in card_list" :key="index" class="membership-card-item"
                    :style="'width:' + card_width + 'px;height:' + card_height + 'px;margin-right:' + card_gap + 'px;'">
                    <!-- 背景（优先当前等级图片，其次全局默认） -->
                    <image v-if="item.card_bg_image || card_bg" class="membership-card-bg" :src="item.card_bg_image || card_bg" mode="aspectFill"></image>
                    <view v-else class="membership-card-bg membership-card-bg-default"></view>
                    <!-- 半透明遮罩，保证文字可读 -->
                    <view class="membership-card-mask"></view>

                    <!-- 当前等级 / 今日生日标签 -->
                    <view v-if="item.is_current == 1" class="membership-card-tag membership-card-current-tag">当前等级</view>
                    <view v-if="item.is_current == 1 && is_birthday_today == 1" class="membership-card-tag membership-card-birthday-tag">今日生日</view>

                    <!-- 卡片内容 -->
                    <view class="membership-card-content">
                        <!-- 标题区：等级名 + 升级条件 -->
                        <view class="membership-card-head">
                            <view class="membership-card-name">{{ item.name }}</view>
                            <view class="membership-card-subtitle">{{ level_subtitle(item) }}</view>
                        </view>

                        <!-- 权益三列：日常优惠 / 生日优惠 / 干洗服务 -->
                        <view class="membership-card-benefits">
                            <view class="membership-card-benefit">
                                <view class="membership-card-benefit-value">{{ benefit_value(item.daily) }}</view>
                                <view class="membership-card-benefit-label">日常优惠</view>
                            </view>
                            <view class="membership-card-benefit">
                                <view class="membership-card-benefit-value">{{ benefit_value(item.birthday) }}</view>
                                <view class="membership-card-benefit-label">生日优惠</view>
                            </view>
                            <view class="membership-card-benefit">
                                <view class="membership-card-benefit-value">{{ item.dry_clean_service || 0 }}<text class="membership-card-dry-unit">件次/年</text></view>
                                <view class="membership-card-benefit-label">干洗服务</view>
                            </view>
                        </view>

                        <!-- 底部：右下角累计消费 -->
                        <view class="membership-card-footer">我已累计消费 {{ format_money(user_total) }} 元</view>
                    </view>

                    <!-- 底部绿色进度条（卡片下边） -->
                    <view class="membership-card-progress">
                        <view class="membership-card-progress-fill" :style="'width:' + level_progress(item) + '%;'"></view>
                    </view>
                </view>
            </view>
        </scroll-view>
    </view>
</template>
<script>
    const app = getApp();
    export default {
        data() {
            return {
                // 卡片数据
                card_list: [],
                card_bg: '',
                is_birthday_today: 0,
                user_total: 0,
                // 尺寸（px）
                card_width: 0,
                card_height: 0,
                card_gap: 8,
                screen_width: 0,
                // 滚动定位
                scroll_left: 0,
                current_index: 0,
                current_level_id: '',
                // 触摸吸附
                touch_start_x: 0,
                pending_index: null,
                scroll_left_now: 0,
                settle_timer: null,
            };
        },
        created() {
            // 尺寸初始化 + 首次加载（父页面 onShow 会通过 ref 再次刷新）
            this.init_size();
            this.load_data();
        },
        beforeDestroy() {
            this.clear_settle_timer();
        },
        methods: {
            // 金额格式化（去多余小数 0）
            format_money(value) {
                var num = parseFloat(value || 0);
                if (isNaN(num)) {
                    num = 0;
                }
                var str = num.toFixed(2);
                str = str.replace(/\.?0+$/, '');
                return str;
            },
            // 数字整数化（去小数）
            format_int(value) {
                var num = parseInt(value || 0, 10);
                return isNaN(num) ? 0 : num;
            },
            // 等级副标题（等级门槛：累计消费超过规则最小值）
            level_subtitle(item) {
                var min = parseFloat(item.rules_min || 0);
                var max = parseFloat(item.rules_max || 0);
                if (min > 0) {
                    return '累计消费超过 ' + this.format_int(min) + ' 元';
                }
                // 首级规则最小值通常为 0，避免显示“累计消费超过 0 元”，改用升级目标（规则最大值）文案
                if (max > 0) {
                    return '累计消费满 ' + this.format_int(max) + ' 元 升级';
                }
                return '累计消费不限';
            },
            // 权益值文本：优先折扣、其次满减、否则无优惠
            benefit_value(benefit) {
                if (benefit != null) {
                    if (benefit.discount_text) {
                        return benefit.discount_text;
                    }
                    if (benefit.full_text) {
                        return benefit.full_text;
                    }
                }
                return '无优惠';
            },
            // 进度条算法：
            // rules_max > 0            -> (user_total - rules_min) / (rules_max - rules_min) * 100%
            // rules_max <= 0（最高级） -> 若 (user_total - rules_min) / rules_min < 1 则 该值*100%，否则 100%
            // 最终收敛到 0% ~ 100%
            level_progress(item) {
                var total = parseFloat(this.user_total || 0);
                var min = parseFloat(item.rules_min || 0);
                var max = parseFloat(item.rules_max || 0);
                var pct = 0;
                if (max > 0) {
                    var span = max - min;
                    if (span > 0) {
                        pct = ((total - min) / span) * 100;
                    } else {
                        pct = (total >= max) ? 100 : 0;
                    }
                } else if (min > 0) {
                    // 最高级（无上限）：从门槛（规则最小值）起步，达到 2 倍门槛即满
                    var rate = (total - min) / min;
                    pct = (rate < 1) ? rate * 100 : 100;
                } else {
                    pct = 100;
                }
                if (pct < 0) {
                    pct = 0;
                }
                if (pct > 100) {
                    pct = 100;
                }
                return pct;
            },
            // 尺寸初始化
            init_size() {
                var window_width = uni.getSystemInfoSync().windowWidth || 375;
                this.screen_width = window_width;
                this.card_width = window_width * 0.85;
                this.card_height = window_width * 0.50;
            },
            // 获取会员卡片数据
            load_data() {
                var self = this;
                uni.request({
                    url: app.globalData.get_request_url('LevelCard', 'User', 'membershiplevel'),
                    method: 'POST',
                    data: {},
                    dataType: 'json',
                    success: (res) => {
                        if (res.data.code == 0 && res.data.data != null) {
                            var data = res.data.data;
                            var list = data.level_list || [];
                            if (list.length > 0) {
                                self.card_bg = data.card_bg_image || '';
                                self.is_birthday_today = data.is_birthday_today || 0;
                                self.user_total = data.user_total || 0;
                                self.current_level_id = data.current_level_id || '';
                                // 当前等级标签：以接口 current_level_id 为准兜底（防止后端 is_current 标记缺失）
                                var current_index = 0;
                                var found = 0;
                                for (var i in list) {
                                    var is_current = (list[i].id == self.current_level_id || list[i].is_current == 1) ? 1 : 0;
                                    list[i].is_current = is_current;
                                    if (is_current == 1 && !found) {
                                        current_index = parseInt(i);
                                        found = 1;
                                    }
                                }
                                self.card_list = list;
                                self.current_index = current_index;
                                // 初始定位当前等级卡
                                setTimeout(function () {
                                    self.scroll_left = self.align_left_of(current_index);
                                }, 100);
                            }
                        }
                    },
                    fail: () => {
                        // 静默失败
                    },
                });
            },
            // ---- 吸附轮播与对齐 ----
            // 取触摸坐标
            touch_point(e) {
                var t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]);
                if (t == null) {
                    return 0;
                }
                return (typeof t.pageX !== 'undefined') ? t.pageX : (t.clientX || 0);
            },
            touch_start_event(e) {
                this.clear_settle_timer();
                this.pending_index = null;
                this.touch_start_x = this.touch_point(e);
            },
            touch_end_event(e) {
                var end_x = this.touch_point(e);
                var delta = this.touch_start_x - end_x;
                // 轻滑超过阈值则翻到相邻一张，否则吸附到最近一张
                if (Math.abs(delta) >= 30) {
                    var count = this.card_list.length;
                    var idx = this.current_index + (delta > 0 ? 1 : -1);
                    idx = Math.max(0, Math.min(count - 1, idx));
                    this.pending_index = idx;
                } else {
                    this.pending_index = null;
                }
                this.arm_settle();
            },
            scroll_handle_event(e) {
                this.scroll_left_now = e.detail.scrollLeft;
                // 惯性/拖动停止后吸附
                this.arm_settle();
            },
            clear_settle_timer() {
                if (this.settle_timer != null) {
                    clearTimeout(this.settle_timer);
                    this.settle_timer = null;
                }
            },
            arm_settle() {
                this.clear_settle_timer();
                var self = this;
                this.settle_timer = setTimeout(function () {
                    self.settle();
                }, 200);
            },
            // 吸附：滚到目标对齐位置
            settle() {
                var idx = (this.pending_index != null) ? this.pending_index : this.nearest_index(this.scroll_left_now);
                if (this.card_list.length <= 1) {
                    idx = 0;
                }
                var left = this.align_left_of(idx);
                if (Math.abs(this.scroll_left_now - left) > 1) {
                    this.scroll_left = left;
                }
                this.current_index = idx;
                this.pending_index = null;
            },
            // 当前滚动位置对应的最近卡片下标
            nearest_index(scroll) {
                var best = 0;
                var best_delta = 1e9;
                for (var i = 0; i < this.card_list.length; i++) {
                    var d = Math.abs(scroll - this.align_left_of(i));
                    if (d < best_delta) {
                        best_delta = d;
                        best = i;
                    }
                }
                return best;
            },
            // 对齐规则：第一张左对齐、最后一张右对齐、其余居中
            align_left_of(index) {
                var count = this.card_list.length;
                if (count <= 1) {
                    return 0;
                }
                var w = this.card_width;
                var g = this.card_gap;
                var step = w + g;
                var content_width = count * w + (count - 1) * g;
                var screen = this.screen_width || (uni.getSystemInfoSync().windowWidth || 375);
                var max_left = content_width - screen;
                if (max_left < 0) {
                    max_left = 0;
                }
                if (index <= 0) {
                    return 0;
                }
                if (index >= count - 1) {
                    return max_left;
                }
                var left = index * step - (screen - w) / 2;
                if (left < 0) {
                    left = 0;
                }
                if (left > max_left) {
                    left = max_left;
                }
                return left;
            },
        },
    };
</script>
<style>
    .membership-card-wrap {
        margin-bottom: 24rpx;
    }
    .membership-card-scroll {
        width: 100%;
        white-space: nowrap;
    }
    .membership-card-row {
        display: inline-flex;
        padding-left: 4rpx;
    }
    .membership-card-item {
        position: relative;
        border-radius: 20rpx;
        overflow: hidden;
        display: inline-block;
        flex-shrink: 0;
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.18);
    }
    /* 背景 */
    .membership-card-bg {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
    }
    .membership-card-bg-default {
        background: linear-gradient(135deg, #3532a7 0%, #6b4ce0 55%, #38c8a8 100%);
    }
    .membership-card-mask {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.2);
    }
    /* 右上标签 */
    .membership-card-tag {
        position: absolute;
        top: 14rpx;
        line-height: 1;
        padding: 8rpx 16rpx;
        border-radius: 999rpx;
        font-size: 20rpx;
        z-index: 3;
    }
    .membership-card-current-tag {
        right: 14rpx;
        background: rgba(255, 255, 255, 0.92);
        color: #333;
    }
    .membership-card-birthday-tag {
        right: 0;
        background: #ff4d4f;
        color: #fff;
        border-radius: 999rpx 0 0 999rpx;
    }
    /* 内容 */
    .membership-card-content {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        padding: 30rpx 30rpx 40rpx;
        color: #ffffff;
    }
    /* 标题区（左上、为背景图 Logo 预留左侧空间） */
    .membership-card-head {
        padding-left: 76rpx;
        padding-right: 20rpx;
        box-sizing: border-box;
    }
    .membership-card-name {
        font-size: 42rpx;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: 2rpx;
        text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.55);
    }
    .membership-card-subtitle {
        margin-top: 8rpx;
        font-size: 23rpx;
        color: rgba(255, 255, 255, 0.9);
        line-height: 1.4;
        text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.55);
    }
    /* 权益三列 */
    .membership-card-benefits {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 8rpx;
        box-sizing: border-box;
    }
    .membership-card-benefit {
        flex: 1;
        text-align: center;
    }
    .membership-card-benefit-value {
        font-size: 32rpx;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.2;
        text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.55);
        word-break: break-all;
        padding: 0 6rpx;
    }
    .membership-card-benefit-label {
        margin-top: 12rpx;
        font-size: 21rpx;
        color: rgba(255, 255, 255, 0.78);
        letter-spacing: 2rpx;
    }
    .membership-card-dry-unit {
        font-size: 20rpx;
        font-weight: 400;
        margin-left: 4rpx;
    }
    /* 底部累计消费（右下角） */
    .membership-card-footer {
        text-align: right;
        font-size: 23rpx;
        color: rgba(255, 255, 255, 0.95);
        letter-spacing: 1rpx;
        text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.55);
    }
    /* 底部绿色进度条：高5px、下边距0、圆角同卡片 */
    .membership-card-progress {
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 10rpx;
        background: rgba(255, 255, 255, 0.28);
        z-index: 2;
    }
    .membership-card-progress-fill {
        height: 100%;
        background: #5fe08a;
        border-radius: 999rpx;
    }
</style>
