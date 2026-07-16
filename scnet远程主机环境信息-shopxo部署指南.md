===============================================================================
                    SCNet远程主机环境信息 - ShopXO 部署指南
                  供 AI 智能体读取，用于部署 ShopXO 系统
===============================================================================

通过 ssh scnet-sunjg 可以免密登录到远程主机
github账户为：sjg_223@126.com / sjg19850223

【重要提示】
本环境是一个正在运行的 Kubernetes Pod 中的计算节点，存在多项关键服务。
部署 ShopXO 时务必严格遵守以下原则：
1. 绝对不要停止、卸载或修改任何已运行的服务。
2. 使用不冲突的新端口（避开下方"已占用端口"列表）。
3. 如果需要数据库，使用新端口安装，不要用已有的 5432/6379。
4. 所有操作以非 root 用户 sunjg 进行，或确保不影响 root 进程。
5. 本环境没有 MySQL/MariaDB 二进制，需要自己安装或从镜像拉取。

===============================================================================
1. 系统基本信息
===============================================================================

操作系统:    Ubuntu 22.04.3 LTS (Jammy Jellyfish)
内核版本:    5.15.0-105-generic (x86_64)
主机名:      crdnotebook-2056205187675406338-scnkt47u2f-36568
IP 地址:     172.20.96.108
DNS:         10.1.4.8, 10.1.4.9
CPU:         Hygon C86 7390 32-core (64 逻辑核)
内存:        2.0 TB 总内存, 约 1.8 TB 可用
Swap:        未配置 (0B)
磁盘 / :     3.5T 总量, 2.6T 已用, 717G 可用 (79%)
inode 使用:  4% (充足)
额外存储:
  /root/private_data -> /dev/mapper/hs_stor_volume_hs_hdd (19P 可用 16P)
  /root/public_data  -> /dev/mapper/hs_stor_volume_hs_ssd (800T 可用 535T)

运行环境:    Kubernetes Pod (容器化)
用户权限:    root (sudo 可用, 但无 systemctl, 因为容器内)

===============================================================================
2. 已占用端口 - 绝对不要使用以下端口
===============================================================================

  端口    服务/进程                          绑定地址    说明
  ------  ---------------------------------  ----------  ----------
  22      SSH 服务                         0.0.0.0     远程连接
  80      Nginx (自建)                     0.0.0.0     Web 服务器
  5432    PostgreSQL (127.0.0.1)             127.0.0.1   本地数据库
  6379    Redis Server (127.0.0.1)           127.0.0.1   本地缓存
  3456    Claude Code Router API             127.0.0.1   AI 代理路由
  58040   致秀原型前端 (127.0.0.1)           127.0.0.1   静态页面
  6080    noVNC Web (127.0.0.1)             0.0.0.0     远程桌面 web
  6090    noVNC Web - Remote Chrome (127.0.0.1) 0.0.0.0  Chrome 远程调试
  8888    Jupyter Lab                        0.0.0.0     AI 开发平台
  5900    Xvfb (IPv6)                       [::]        虚拟显示器
  5901    Xvfb (:1 显示)                   0.0.0.0     虚拟显示器
  5999    Xvfb (:99 显示 + VNC)            0.0.0.0     远程 Chrome 显示器
  8000    vLLM (Qwen3.6-35B)               0.0.0.0     大模型推理服务
  8002    MCP 服务                           0.0.0.0     AI 工具调用
  8003    MCP Test 服务                      0.0.0.0     MCP 测试
  8004    Python 服务                        0.0.0.0     未知 Python
  8010    Python 服务                        0.0.0.0     未知 Python
  48203   动态端口 (可能 VNC 相关)           0.0.0.0     临时

其他 127.0.0.1 上的临时端口 (33137-44343): 多个内部服务，不要占用。

建议 ShopXO 使用端口:
  HTTP:  8080 (避免与 80 冲突)
  HTTPS: 8443
  PHP-FPM: 9000 (或 9080)
  MySQL: 3380 (或 3307) - 不要用 3306 (未被占用但建议用非标准)

===============================================================================
3. Nginx 配置详情 (自建版本, 非 apt 安装)
===============================================================================

Nginx 版本:    nginx/1.26.2
二进制路径:    /root/private_data/sun/Download/nginx/sbin/nginx
主配置路径:    /root/private_data/sun/Download/nginx/conf/nginx.conf
配置语法:      include ./vllm-proxy.conf; 和 include ./zhixiu-58043.conf;

当前 nginx.conf 内容 (精简):
  worker_processes  1;
  events { worker_connections  1024; }
  http {
      include       mime.types;
      default_type  application/octet-stream;
      sendfile        off;
      keepalive_timeout  65;
      include ./vllm-proxy.conf;
      include ./zhixiu-58043.conf;
  }

Nginx 根目录: /root/private_data/sun/Download/nginx/html/
Nginx 日志:   /root/private_data/sun/Download/nginx/logs/
  - access.log
  - error.log

主配置文件 vllm-proxy.conf 代理了以下后端:
  /             -> 127.0.0.1:3456 (Claude Router)
  /v1/*         -> 127.0.0.1:8000 (vLLM)
  /mcp/*        -> 127.0.0.1:8002 (MCP)
  /mcp_test/*   -> 127.0.0.1:8003 (MCP Test)
  /vnc/*        -> 127.0.0.1:6080 (noVNC)
  /remote-chrome/* -> 127.0.0.1:6090 (Chrome VNC)
  /zhixiu/*     -> 127.0.0.1:58040 (静态页面)
  /api/*        -> 127.0.0.1:3000 (NestJS 后端)
  /zx-admin/*   -> 127.0.0.1:3001 (React 前端)
  /zx-assets/*  -> 本地 /root/private_data/sun/storage/zhixiu-assets/

重要:
- 当前 Nginx 以 root 用户运行
- Nginx 监听 0.0.0.0:80
- server_name: c-2056205187675406338.qdai.scnet.cn
- 如果需要让 Nginx 也代理 ShopXO，需要在 vllm-proxy.conf 中加 location block，
  或创建一个单独的 .conf 文件 include 进来
- 或者让 ShopXO 用独立端口 (8080) 直接访问

===============================================================================
4. 已安装软件清单
===============================================================================

[已安装 - 版本]
- Node.js:    v22.14.0 (路径: /root/private_data/sun/tools/node/bin/node)
- NPM:        10.9.2
- Python:     3.11.9 (系统)
- Conda:      24.7.1 (路径: /opt/conda, base 和 vllm 两个环境)
- Docker:     29.6.1 (Community, 但当前无权限访问 socket)
- Git:        2.34.1
- Curl:       8.5.0
- Wget:       可用

[未安装 - 需要部署 ShopXO 时安装]
- PHP:        未安装 (需要 PHP 8.x, ShopXO 要求 >= 8.0)
- PHP-FPM:    未安装 (需要)
- Composer:   未安装
- MySQL:      未安装 (系统中有 PostgreSQL 服务但不带 psql 客户端)
- MariaDB:    未安装
- Apache:     未安装
- Redis CLI:  未安装 (但有 Redis 服务在运行)

注意：全部软件和工具包等，必选安装到目录：/root/private_data/sun/tools/下，这是最高等级的约束，必须遵守。

可用的包管理器: apt (Ubuntu 22.04)

PHP 安装建议 (由于是容器环境, apt 安装最方便):
  apt update
  apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-pdo php8.1-mbstring \
      php8.1-xml php8.1-curl php8.1-gd php8.1-zip php8.1-intl php8.1-opcache \
      php8.1-bcmath php8.1-redis

MySQL 安装建议 (apt 安装, 用非标准端口):
  apt install -y mysql-server-8.0
  注意: 容器内无 systemctl, 需要用 mysqld_safe 或 mysql 命令直接启动
  建议使用端口 3380 而非默认 3306

===============================================================================
5. 关键目录结构
===============================================================================

/root/private_data/sun/
  |-- Download/        # 下载的软件包和自建工具
  |   |-- nginx/       # 自建 Nginx 完整安装目录
  |-- claudeWorkspace/ # Claude 项目工作区
  |-- hermesWorkspac/  # Hermes 项目工作区 (当前目录)
  |-- storage/         # 通用存储目录
  |-- test/            # 测试目录
  |-- tools/           # 工具集合
      |-- hermes/      # Hermes Agent
      |-- node/        # Node.js 自定义安装
      |-- v2raya/      # V2RayA 代理
      |-- vnc/         # VNC 相关
  |-- code/            # 代码仓库
  |-- codeWhaleWorkspace/
  |-- codexWorkspace/
  |-- opencodeWorkspace/

===============================================================================
6. Docker 状态
===============================================================================

Docker 版本: 29.6.1 (Community)
Docker Compose: 5.2.0
当前 Docker 服务: 无权限访问 socket (permission denied)
  - 需要从 root 用户或加入 docker 组才能使用
  - 如果要用 Docker 部署 ShopXO, 需要先用 root 用户执行

Docker 内没有 PHP/MySQL/Node 相关镜像。

===============================================================================
7. 代理设置 (如果需要下载依赖)
===============================================================================

http_proxy=http://scnkt47u2f:007dbce8@10.1.4.13:3120
https_proxy=http://scnkt47u2f:007dbce8@10.1.4.13:3120

V2RayA 代理 (备选):
  HTTP: 127.0.0.1:20171
  SOCKS5: 127.0.0.1:20170

===============================================================================
8. 防火墙与安全
===============================================================================

- UFW: 未安装或未激活
- SELinux: 未启用
- iptables: 未安装或未配置
- 端口对外全部开放 (容器网络策略由 K8s 控制)


===============================================================================
9. 禁止操作清单 (Do NOT Do)
===============================================================================

[!] 不要停止任何运行中的进程
[!] 不要修改 /opt/conda 相关设置
[!] 不要安装/修改系统 Python
[!] 不要修改 SSH 配置 (port 22)
[!] 不要修改 /etc/hosts (K8s 核心配置)
[!] 不要删除或修改 Jupyter Lab (port 8888)
[!] 不要安装/修改 Nginx (apt 的 nginx 会和自建冲突)
[!] 不要修改现有的 nginx.conf 中已有的 include
[!] 不要使用端口 80 (已被 Nginx 占用)
[!] 不要修改 /etc/resolv.conf (K8s DNS)
[!] 不要尝试 systemctl 操作 (容器内不可用)
[!] 不要修改 /root/private_data 中的已有项目

===============================================================================
                            文档生成结束
                      生成时间: 2026-07-16
                    用于: ShopXO 系统部署
===============================================================================
