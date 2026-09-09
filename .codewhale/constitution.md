# ShopXO 服装商城项目 — CodeWhale 宪法规则

> 本文件为项目级宪法规则，在 CodeWhale 中具有最高权威（仅次于用户本轮指令）。
> 所有 Agent 角色、自动化流程、Skill 和 Hook 均需遵守本宪法。

---

## 一、项目身份

| 属性 | 值 |
|------|-----|
| **项目名称** | ShopXO 服装商城 (Clothing Mall) |
| **基于开源** | [ShopXO](https://github.com/gongfuxiang/shopxo) v3.x |
| **最终目标** | 服装类微信小程序商城（含 H5 + PC 管理后台） |
| **许可证** | MIT（二次开发遵循 MIT 协议） |
| **版本控制** | Git + GitHub |
| **工作空间** | `C:\Users\sugon\codewhaleWorkspace\shopXO` |

---

## 二、技术栈宪法

### 2.1 核心技术栈（不可随意变更）

| 层级 | 技术 | 版本要求 |
|------|------|----------|
| **语言** | PHP | >= 8.0.0 |
| **框架** | ThinkPHP | 8.x (topthink/framework) |
| **ORM** | ThinkPHP ORM | * |
| **数据库** | MySQL | >= 5.6 (utf8mb4) |
| **缓存** | Redis / File | 可配置 |
| **前端(PC)** | HTML5 + CSS3 + jQuery | - |
| **前端(H5)** | 自适应 + uni-app | - |
| **小程序** | uni-app (Vue 3) | shopxo-uniapp |
| **Web 服务器** | Nginx / Apache | - |
| **包管理** | Composer | PHP |
| **包管理** | npm/yarn | 前端 |

### 2.2 项目多应用架构

```
app/
├── index/      # PC + H5 前端商城
├── admin/      # 后台管理系统
├── api/        # API 接口（小程序/app 调用）
├── install/    # 安装向导
├── plugins/    # 插件目录
├── service/    # 公共服务层
├── module/     # 功能模块
├── lang/       # 多语言包（zh/en/cht/spa）
└── tpl/        # 公共模板
```

### 2.3 关键依赖

```json
{
  "topthink/framework": "*",
  "topthink/think-orm": "*",
  "topthink/think-multi-app": "*",
  "topthink/think-view": "*",
  "phpoffice/phpspreadsheet": "^1.4",
  "overtrue/pinyin": "*",
  "picqer/php-barcode-generator": "*",
  "phpmailer/phpmailer": "*"
}
```

---

## 三、开发流程宪法

### 3.1 分支策略（Git Flow 变体）

| 分支 | 用途 | 命名规则 |
|------|------|----------|
| `master` | 生产就绪代码，与上游 `shopxo/master` 保持同步 | `master` |
| `develop` | 开发主线，合并各功能模块 | `develop` |
| `feature/xxx` | 功能模块独立开发 | `feature/membership`, `feature/clothing-theme` |
| `hotfix/xxx` | 紧急修复 | `hotfix/order-bug` |
| `release/x.x.x` | 发布准备 | `release/v1.1.0` |

**规则：**
- 上游更新时，先合并到 `master`，再 rebase 到 `develop`
- 每个独立模块必须使用独立 `feature/*` 分支开发
- 合并到 `develop` 前必须通过自动验证和测试
- 禁止直接推送到 `master` 和 `develop`

### 3.2 二次开发模块拆分

| 模块 | 分支 | 说明 |
|------|------|------|
| 服装主题装修 | `feature/clothing-theme` | DIY 拖拽装修、服装分类、品牌页 |
| 会员体系定制 | `feature/membership` | 会员等级、积分、成长值、权益 |
| 微信小程序 | `feature/wechat-miniapp` | uni-app 小程序适配与发布 |
| 服装商品管理 | `feature/clothing-goods` | 尺码表、颜色SKU、搭配推荐 |
| 订单增强 | `feature/order-enhance` | 退换货流程、物流追踪 |
| 营销活动 | `feature/marketing` | 秒杀、拼团、优惠券定制 |

### 3.3 合并门禁（Merge Gate）

合并到 `develop` 和 `master` 前必须通过：

1. **语法检查**：`php -l` 扫描所有修改的 PHP 文件
2. **代码风格**：PSR-12 标准
3. **单元测试**：PHPUnit（如存在）
4. **数据库迁移**：SQL 变更必须有回滚脚本
5. **安全扫描**：无硬编码密钥、无 SQL 注入风险
6. **CodeWhale 自动 Review**：由 `reviewer` Agent 自动审查

### 3.4 提交信息规范

```
<type>(<scope>): <subject>

type: feat | fix | docs | style | refactor | perf | test | chore
scope: admin | api | index | service | module | config
```

---

## 四、CodeWhale Agent 角色定义

### 4.1 固定角色

| 角色 | Profile | 职责 |
|------|---------|------|
| **架构师** | `architect` | 方案设计、技术选型、代码审查 |
| **开发工程师** | `builder` | 功能实现、代码编写 |
| **审查员** | `reviewer` | PR 审查、代码质量把控 |
| **测试员** | `verifier` | 自动测试、回归验证 |
| **运维** | `deployer` | CI/CD、服务器部署 |

### 4.2 角色激活规则

- `builder`：开发 `feature/*` 分支时激活
- `reviewer`：合并 PR 时自动激活
- `verifier`：合并门禁阶段自动激活
- `deployer`：`release/*` 分支打 tag 时激活
- `architect`：重大设计决策时手动激活

---

## 五、自动化规则

### 5.1 Hooks（Git Hooks + CodeWhale Hooks）

| Hook | 触发时机 | 动作 |
|------|----------|------|
| `pre-commit` | git commit 前 | PHP 语法检查、代码风格检查 |
| `pre-push` | git push 前 | 运行单元测试 |
| `post-merge` | 合并后 | 清理缓存、更新依赖 |
| `post-checkout` | 切换分支后 | 检查 composer 依赖是否需要更新 |

### 5.2 CI/CD 流水线（GitHub Actions）

```
Push feature/* 
  → Lint + Test
    → PR to develop 
      → Full Test Suite + Security Scan
        → Merge to develop
          → Build assets
            → Deploy to staging

Tag release/*
  → Build production assets
    → Deploy to production
      → Smoke test
```

### 5.3 自动部署

| 环境 | 服务器 | 触发条件 |
|------|--------|----------|
| **本地开发** | `localhost:8000` | PHP built-in server |
| **测试环境** | 待定 | `develop` 分支推送 |
| **生产环境** | 待定 | `release/*` tag |

---

## 六、配置文件管理

### 6.1 敏感信息

以下文件**绝不提交**到 Git：

- `.env`（含数据库密码、API 密钥）
- `config/database.php`（安装后生成）
- `rsakeys/*`（密钥文件）
- `runtime/*`（运行时文件）
- 任何含 `sk-`, `api_key`, `password` 的文件

### 6.2 环境配置

```
# .env (不提交)
APP_DEBUG = true
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = shopxo_clothing
DB_USER = root
DB_PASS = 
```

使用 `example.env` 作为模板，不包含真实凭据。

---

## 七、项目对话记录规则

### 7.1 prompt.log

每次对话结束后，将决策和关键信息以 **Q&A 格式** 追加到 `prompt.log`：

```markdown
## [2026-07-16] 会话#1

**Q: 项目初始化和技术选型？**
A: 基于 ShopXO (ThinkPHP 8) 进行服装商城二次开发，最终部署微信小程序。

**Q: 分支策略？**
A: Git Flow 变体，每个模块独立 feature 分支，合并前自动验证。

**Q: CI/CD 方案？**
A: GitHub Actions，分阶段 lint → test → build → deploy。
```

### 7.2 config.list

所有项目配置项的索引文件，记录非敏感的配置信息和路径。

---

## 八、不可逾越的红线

1. **绝不提交** 任何密钥、密码、Token 到 Git
2. **绝不修改** `vendor/` 目录中的第三方代码（使用 Composer 依赖覆盖）
3. **绝不跳过** 合并门禁（除非用户明确指令）
4. **绝不上传** 生产数据库到公开位置
5. **绝不直接修改** `master` 分支（始终通过 PR）
6. **使用简体中文** 进行所有 reasoning 和回复（代码、路径、命令保持原文）

---

*本宪法由 CodeWhale 在 2026-07-16 根据用户需求自动生成，随项目演进持续更新。*
