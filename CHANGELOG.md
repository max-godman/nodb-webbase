# Changelog

## [1.5.0] - 2026-05-30

### Added: Country Manager

**Core changes:**
- New `adm/country.php` — Full CRUD for country database with JSON storage
- `data/country.log` — Raw country data in JSON format (id, code, country, cn, ads, letter)
- `data/site_country.log` — Compiled HTML `<select>` cache, generated via "Regen Cache" button
- `{site:country_en}` / `{site:country_cn}` — Front-end placeholders for English/Chinese country dropdown
- `inc/sys_inc.php` — Added `getCountryName(code, lang)` helper function
- Country tab: search by code/name, filter by Active/Pending status, bulk edit, single add

**New files:**
- `adm/country.php` — Country manager admin page
- `data/country.log` — Country raw data (JSON)
- `data/site_country.log` — Compiled HTML select cache

### Added: IP / User-Agent Security System

**Core changes:**
- `data/site_agent.log` — 4-level rule system: 1=IP blacklist (503), 2=UA blacklist (503), 3=IP whitelist (pass), 4=UA whitelist (pass)
- `sys_site_agent_block` config (default 1) — Master switch: 0=off, 1=block matching agents
- `sys_site_agent_log` config (default 1) — Logging switch: 0=no, 1=log blocked requests
- `setup.php` — `createSystemConfig()` now generates agent block/log params on install
- Front-end blocking integrated into `index.php` dispatch flow

**New files:**
- `data/site_agent.log` — IP/UA black/whitelist rules (48 entries covering major search engines)

### Added: AJAX Popup System

**Core changes:**
- `tpl/code_ajax.log` — Generic JSON response handler, applies `{sys_xxx}` / `{ui_xxx}` placeholders
- `tpl/ajax_{type}.log` — Popup content files (e.g. `ajax_terms.log`, `ajax_email.log`)
- Front-end `openshow(type)` JavaScript function loads popup via `/ajax?type=...`

**New files:**
- `tpl/code_ajax.log` — AJAX popup JSON handler
- `tpl/ajax_terms.log` — Terms of Service popup content
- `tpl/ajax_email.log` — Contact email popup content

### Added: Sitemap Auto-Generation

**Core changes:**
- `tpl/code_sitemap.log` — PHP generator for `/sitemap.xml` with 3-layer merge
- `data/sitemap_manual.log` — Manually defined sitemap entries (pipe-delimited)
- `data/sitemap_tag.log` — Tag-based sitemap entries (auto-generated)
- `data/sitemap_info.log` — Info-based sitemap entries (auto-generated)
- Route `/sitemap.xml` mapped to code page key=sitemap

**New files:**
- `tpl/code_sitemap.log` — Sitemap XML generator
- `data/sitemap_manual.log` — Manual sitemap entries
- `data/sitemap_tag.log` — Tag sitemap entries
- `data/sitemap_info.log` — Info sitemap entries

### Added: Friend Links Manager

**Core changes:**
- New `adm/links.php` — Quick add and bulk edit with inline delete
- `inc/link.log` — Data storage as PHP return array
- `{site:links}` placeholder — Renders `<ul class="friend-links">` in any template

**New files:**
- `adm/links.php` — Friend links admin page

### Added: Front-end Navigation System

**Core changes:**
- New `adm/nav.php` — Add/edit/reorder nav items, set active/paused status
- `data/site_nav.log` — Pipe-delimited storage (sort|text|link|status)
- `{site:menu}` placeholder — Renders navigation with active state highlighting

**New files:**
- `adm/nav.php` — Front-end menu manager
- `data/site_nav.log` — Navigation data

### Added: Change Password

**Core changes:**
- New `adm/pwd.php` — Change current admin password with old password verification
- SHA256 verification, minimum 6 characters, session cookies cleared on success

**New files:**
- `adm/pwd.php` — Password change page

### Added: Extended Placeholder System

**New placeholders available everywhere (templates, static pages, code blocks):**
- `{site:menu}` — Front-end navigation menu
- `{site:router}` — Canonical URL (auto-detected)
- `{site:path}` — Breadcrumb path (from code blocks)
- `{site:links}` — Friend links list
- `{site:country_en}` / `{site:country_cn}` — Country dropdown (after Regen Cache)
- `{code:code_title}` — Code block title variable
- All `{ui_xxx}` placeholders from `inc/sys_ui.php`

### Added: Database Table Name Constants

- `data/sys_sql_table.log` — Defines `TABLE_INFO`, `TABLE_PIC`, `TABLE_TAG`, `TABLE_BLKIP`, `TABLE_SPAM`, `TABLE_CITY` constants

### Changed: Expanded Language Options in Setup

- `setup.php` language selector now supports 18 languages: zh, zh-TW, en, es, fr, de, ru, uk, ja, ko, pt, it, pl, sv, no, fi, tr, vi, nl, da, id, ms

### Changed: Admin Panel Restructuring

- **New top-level menu items**: Country, Links, Change Password
- **Sys.php now has 12 sub-tabs**: System Info, Config, Accounts, Params, UI Text, File Editor, Pics, Menu, Add Page, System Log, SQL
- Data sub-directory expanded with 10+ new data files

**Modified files:**
- `adm/inc_menu.log` — Added Country, Links, Password menu entries
- `adm/sys.php` — Added sub-tabs for menu management, add page, etc.
- `inc/sys_inc.php` — Added `getCountryName()`, enhanced placeholder resolution
- `data/editor_files.log` — Registered all new editable files
- `setup.php` — Added agent block/log to `createSystemConfig()`

---

### 新增：国家数据库管理

**核心变化：**
- 新增 `adm/country.php` — 国家数据完整 CRUD，JSON 存储
- `data/country.log` — 原始国家数据 JSON 格式（id, code, country, cn, ads, letter）
- `data/site_country.log` — 编译后的 HTML `<select>` 缓存，通过"Regen Cache"按钮生成
- `{site:country_en}` / `{site:country_cn}` — 前端中英文国家下拉占位符
- `inc/sys_inc.php` — 新增 `getCountryName(code, lang)` 辅助函数
- 国家管理页：按代码/名称搜索，按 Active/Pending 筛选，批量编辑，单条添加

**新增文件：**
- `adm/country.php` — 国家管理后台页面
- `data/country.log` — 国家原始数据（JSON）
- `data/site_country.log` — 编译后的 HTML 下拉缓存

### 新增：IP / User-Agent 安全系统

**核心变化：**
- `data/site_agent.log` — 4级规则系统：1=IP黑名单(503)、2=UA黑名单(503)、3=IP白名单(放行)、4=UA白名单(放行)
- `sys_site_agent_block` 配置（默认1）— 总开关：0=关闭，1=拦截匹配的agent
- `sys_site_agent_log` 配置（默认1）— 日志开关：0=不记录，1=记录被拦截请求
- `setup.php` — `createSystemConfig()` 安装时生成 agent 拦截/日志参数
- 前端拦截集成到 `index.php` 分发流程

**新增文件：**
- `data/site_agent.log` — IP/UA 黑白名单规则（48条，覆盖主流搜索引擎）

### 新增：AJAX 弹窗系统

**核心变化：**
- `tpl/code_ajax.log` — 通用 JSON 响应处理器，自动应用 `{sys_xxx}` / `{ui_xxx}` 占位符
- `tpl/ajax_{type}.log` — 弹窗内容文件（如 `ajax_terms.log`、`ajax_email.log`）
- 前端 `openshow(type)` JavaScript 函数通过 `/ajax?type=...` 加载弹窗

**新增文件：**
- `tpl/code_ajax.log` — AJAX 弹窗 JSON 处理器
- `tpl/ajax_terms.log` — 服务条款弹窗内容
- `tpl/ajax_email.log` — 联系邮箱弹窗内容

### 新增：站点地图自动生成

**核心变化：**
- `tpl/code_sitemap.log` — `/sitemap.xml` PHP 生成器，支持3层数据合并
- `data/sitemap_manual.log` — 手动定义的站点地图条目（管道分隔）
- `data/sitemap_tag.log` — 标签类站点地图条目（自动生成）
- `data/sitemap_info.log` — 信息类站点地图条目（自动生成）
- 路由 `/sitemap.xml` 映射到 code 页面 key=sitemap

**新增文件：**
- `tpl/code_sitemap.log` — 站点地图 XML 生成器
- `data/sitemap_manual.log` — 手动站点地图条目
- `data/sitemap_tag.log` — 标签站点地图条目
- `data/sitemap_info.log` — 信息站点地图条目

### 新增：友情链接管理

**核心变化：**
- 新增 `adm/links.php` — 快速添加和批量编辑（含删除）
- `inc/link.log` — 数据存储为 PHP return array
- `{site:links}` 占位符 — 在任何模板中渲染 `<ul class="friend-links">`

**新增文件：**
- `adm/links.php` — 友情链接后台页面

### 新增：前端导航系统

**核心变化：**
- 新增 `adm/nav.php` — 添加/编辑/排序导航项，设置启用/暂停状态
- `data/site_nav.log` — 管道分隔存储（sort|text|link|status）
- `{site:menu}` 占位符 — 渲染带活跃状态高亮的导航

**新增文件：**
- `adm/nav.php` — 前端菜单管理页
- `data/site_nav.log` — 导航数据

### 新增：修改密码

**核心变化：**
- 新增 `adm/pwd.php` — 修改当前管理员密码，需验证旧密码
- SHA256 验证，最少6位，成功后清除 Cookie 重新登录

**新增文件：**
- `adm/pwd.php` — 密码修改页

### 新增：扩展占位符系统

**所有位置均可使用的新占位符（模板、静态页、代码块）：**
- `{site:menu}` — 前端导航菜单
- `{site:router}` — 规范 URL（自动检测）
- `{site:path}` — 面包屑导航（来自代码块）
- `{site:links}` — 友情链接列表
- `{site:country_en}` / `{site:country_cn}` — 国家下拉菜单（需 Regen Cache）
- `{code:code_title}` — 代码块标题变量
- 所有来自 `inc/sys_ui.php` 的 `{ui_xxx}` 占位符

### 新增：数据库表名常量

- `data/sys_sql_table.log` — 定义 `TABLE_INFO`、`TABLE_PIC`、`TABLE_TAG`、`TABLE_BLKIP`、`TABLE_SPAM`、`TABLE_CITY` 常量

### 变更：安装程序语言选项扩展

- `setup.php` 语言选择器现支持 18 种语言：zh, zh-TW, en, es, fr, de, ru, uk, ja, ko, pt, it, pl, sv, no, fi, tr, vi, nl, da, id, ms

### 变更：后台菜单重组

- **新增顶级菜单项**：Country、Links、Change Password
- **Sys.php 现含 12 个子标签页**：系统信息、配置、账号、参数、界面文字、文件编辑器、图片、菜单、添加后台文件、系统日志、SQL
- data 子目录新增 10+ 个数据文件

**修改文件：**
- `adm/inc_menu.log` — 新增 Country、Links、Password 菜单项
- `adm/sys.php` — 新增菜单管理、添加后台文件等子标签
- `inc/sys_inc.php` — 新增 `getCountryName()`，增强占位符解析
- `data/editor_files.log` — 注册所有新增可编辑文件
- `setup.php` — 在 `createSystemConfig()` 新增 agent 拦截/日志参数

## [1.0.3] - 2026-05-13

### Changed: Support DESC shorthand in SQL executor + add Common SQL Examples

**Core changes:**
- `inc/sys_sql_func.php` — Added `DESC` to `getStatementType()` `$knownTypes`, `extractTableName()` switch case (with regex `DESC(?:RIBE)?`), `checkTablePermission()` level-5 read switch, and `executeSqlStatement()` query-branch `in_array()`
- `adm/sys.php` — Added 5 rows to Common SQL Examples table: `SHOW TABLES`, `DESC`, `SHOW CREATE TABLE`, `SHOW INDEX`, `SELECT COUNT`

**Modified files:**
- `inc/sys_sql_func.php` — DESC support in 4 locations (lines 159, 223, 299, 373)
- `adm/sys.php` — Common SQL Examples table (lines 1582-1606)

### 变更：SQL执行器支持 DESC 简写 + 新增常用 SQL 范例

**核心变化：**
- `inc/sys_sql_func.php` — 在 4 个位置加入 `DESC` 支持：`getStatementType()` 的 `$knownTypes`、`extractTableName()` 的 switch case（正则 `DESC(?:RIBE)?`）、`checkTablePermission()` 的级别5查询开关、`executeSqlStatement()` 的 query 分支 `in_array()`
- `adm/sys.php` — 常用命令范例表新增 5 行：`SHOW TABLES`、`DESC`、`SHOW CREATE TABLE`、`SHOW INDEX`、`SELECT COUNT`

**修改文件：**
- `inc/sys_sql_func.php` — 4 处加入 DESC 支持
- `adm/sys.php` — 常用命令范例表新增 5 行

## [1.0.2] - 2026-05-13

### Changed: File Editor extension whitelist + auto-create empty file on add

**Core changes:**
- New `data/editor_file_type.log` — defines allowed file extensions (one per line, sorted: css, html, log, txt)
- `data/editor_file_type.log` auto-registered in File Editor editable list for manual maintenance
- `adm/sys.php` editor_action=add now validates extension against the whitelist before adding
- If target file doesn't exist on disk, an empty file is automatically created in the selected directory
- Extension-less files are rejected with a clear error message

**New files:**
- `data/editor_file_type.log` — Allowed file extension list for File Editor

**Modified files:**
- `adm/sys.php` — Extension validation + auto-create empty file on add (lines 446-498)
- `data/editor_files.log` — Registered `editor_file_type.log` for editing


### 变更：文件编辑器扩展名白名单 + 添加时自动创建空文件

**核心变化：**
- 新增 `data/editor_file_type.log` — 定义允许的文件扩展名（每行一个，排序：css, html, log, txt）
- `data/editor_file_type.log` 自动注册到 File Editor 可编辑列表，支持手动维护
- `adm/sys.php` 的 `editor_action=add` 现在先校验扩展名是否在白名单中，再允许添加
- 如果目标文件在磁盘上不存在，自动在选定目录创建空文件
- 无扩展名的文件直接拒绝，并有清晰的错误提示

**新增文件：**
- `data/editor_file_type.log` — File Editor 允许的扩展名列表

**修改文件：**
- `adm/sys.php` — 扩展名校验 + 添加时自动创建空文件（第 446-498 行）
- `data/editor_files.log` — 注册 `editor_file_type.log` 到可编辑列表


## [1.0.1] - 2026-05-13

### Changed: Move sys_log.log from data/ to inc/

**Reason:**
- Prevent sys_log.log from being editable via `adm/sys.php?type=editor` (File Editor)
- The `data/` directory is registered in File Editor's allowed directories, making `data/sys_log.log` modifiable from admin panel
- Moving to `inc/` removes it from File Editor scope, protecting log integrity

**Modified files:**
- `inc/sys_inc.php` — Changed default log path in `writeSysLog()` and `readSysLog()` from `__DIR__ . '/../data/sys_log.log'` to `__DIR__ . '/sys_log.log'`
- `README.md` — Updated directory listings (EN & CN)

### 变更：系统日志 sys_log.log 从 data/ 移至 inc/

**原因：**
- 防止 sys_log.log 被 `adm/sys.php?type=editor`（文件编辑器）修改
- `data/` 目录注册在 File Editor 可编辑目录中，导致 `data/sys_log.log` 可在后台被修改
- 移至 `inc/` 后脱离 File Editor 范围，保护日志完整性

**修改文件：**
- `inc/sys_inc.php` — `writeSysLog()` 和 `readSysLog()` 默认日志路径从 `__DIR__ . '/../data/sys_log.log'` 改为 `__DIR__ . '/sys_log.log'`
- `README.md` — 更新目录结构（英文和中文）

## [1.0.0] - 2026-05-13

### New: SQL Management — Database config + SQL executor + protected table whitelist

**Core changes:**
- New `type=sql` tab in `adm/sys.php` — two-section layout: Database Config + SQL Executor
- Database Config: read/write `inc/sys_sql.php`, test connection, status display (not configured/connected/failed)
- SQL Executor: single-statement execution with PDO, SELECT rendered as HTML table
- Protected table whitelist (`data/sql_protected.log`): level-based permission (10=full/9=alter fields/8=CRUD rows/5=read only)
- CREATE TABLE always allowed, auto-adds new table to whitelist with level 8
- Tables not in whitelist: cannot be operated on
- No-table SQL (SELECT 1, SHOW TABLES): directly allowed
- SQL helper library `inc/sys_sql_func.php` — 10 functions for config, permission, execution
- Auto-registered `data/sql_protected.log` in File Editor for manual editing

**New files:**
- `inc/sys_sql_func.php` — SQL helper function library
- `data/sql_protected.log` — Protected table permission whitelist

**Modified files:**
- `adm/sys.php` — New tab link, POST handler (save_config + execute_sql), case 'sql' page output
- `data/editor_files.log` — Registered sql_protected.log for File Editor

### 新增：SQL管理 — 数据库配置 + SQL执行器 + 受保护表白名单

**核心变化：**
- `adm/sys.php` 新增 `type=sql` 标签页 — 分两段布局：数据库配置 + SQL 执行器
- 数据库配置：读写 `inc/sys_sql.php`，测试连接，状态展示（未配置/已连接/连接失败）
- SQL 执行器：单条语句执行，SELECT 结果渲染为 HTML 表格
- 受保护表白名单（`data/sql_protected.log`）：分级权限（10=完全/9=改字段/8=改数据/5=仅查询）
- CREATE TABLE 始终放行，执行后自动加入白名单（级别 8）
- 不在白名单的表：禁止操作
- 不涉及表的 SQL（SELECT 1, SHOW TABLES）：直接放行
- SQL 函数库 `inc/sys_sql_func.php` — 10 个函数覆盖配置、权限、执行全流程
- `data/sql_protected.log` 自动注册到 File Editor，支持手动编辑

**新增文件：**
- `inc/sys_sql_func.php` — SQL 辅助函数库
- `data/sql_protected.log` — 受保护表权限白名单

**修改文件：**
- `adm/sys.php` — 新 Tab、POST handler、case 'sql' 页面输出
- `data/editor_files.log` — 注册 sql_protected.log 到 File Editor

## [0.10.0] - 2026-05-13

### New: Add Admin Page — Auto-generate backend pages

**Core changes:**
- New `type=addpage` tab in `adm/sys.php` — Creates admin files from a form
- Generates `adm/add_{name}.php` (controller with `$pageLevel + auth`) + `data/add_{name}.log` (template with head/foot, `$pageTitle` at top)
- Auto-registers every new `.log` in File Editor editable list
- On success: redirects to File Editor for immediate `.log` editing
- All created pages auto-register to `data/add_menu.log` (auto-created on first use)
- `data/add_menu.log` includes an "Edit" link to itself on creation, registered in File Editor
- `data/add_menu.log` menu auto-loaded and displayed on all `sys.php` sub-tabs
- Generated `add_*.php` pages also load and display the same menu bar (absent menu = no display)
- `$pageLevel = 20` stays in `.php` controller (before auth), `$pageTitle` in `.log`

**Modified files:**
- `adm/sys.php` — New tab link, POST handler, form UI; loading + display of `data/add_menu.log`

### 新增：添加后台文件 — 自动生成后台页面

**核心变化：**
- `adm/sys.php` 新增 `type=addpage` 标签页 — 表单创建后台管理文件
- 生成 `adm/add_{name}.php`（含 `$pageLevel + auth` 的控制器）+ `data/add_{name}.log`（含头尾模板，`$pageTitle` 置顶）
- 每次创建自动将 `.log` 注册到 File Editor 可编辑列表
- 创建成功跳转到 File Editor 立即编辑 `.log`
- 所有创建的页面自动注册到 `data/add_menu.log`（首次自动创建）
- `data/add_menu.log` 首次创建时自带 "Edit" 编辑链接，并注册到 File Editor
- `data/add_menu.log` 菜单自动加载到 `sys.php` 所有子栏页面顶部
- 生成的 `add_*.php` 页面同样加载并显示该菜单（无菜单文件时不显示）
- `$pageLevel = 20` 留在 `.php` 控制器（在 auth 前面），`$pageTitle` 在 `.log` 中

**修改文件：**
- `adm/sys.php` — 新 Tab、POST handler、表单 UI；加载和显示 `data/add_menu.log`

---

### New: System Functions Test in msg.php

**Core changes:**
- Copied all 20 test sections from `test.php` into `adm/msg.php` as a collapsible card group
- Includes: PHP extensions, image upload env, domain/email/IP/geo, UUID, time functions, device/browser detection, date formats, input filtering, other common functions, JSON
- Uses admin panel template styling (cards/tables) instead of standalone HTML
- `sys_inc.php` already loaded via `auth.php` — no additional includes needed

**Modified files:**
- `adm/msg.php` — Added System Functions Test card (69 → 290 lines)

### 新增：msg.php 集成系统函数测试

**核心变化：**
- 将 `test.php` 全部 20 个测试区块复制到 `adm/msg.php` 作为可展开的卡片组
- 包括：PHP 扩展、图片上传环境、域名/邮箱/IP/地理位置、UUID、时间函数、设备/浏览器检测、日期格式、输入过滤、其他常用函数、JSON
- 使用后台模板样式（卡片/表格）替代独立 HTML
- `auth.php` 已加载 `sys_inc.php` — 无需额外引入

**修改文件：**
- `adm/msg.php` — 新增系统函数测试卡片（69 → 290 行）

## [0.9.0] - 2026-05-12

### Major: Routing & Page Management Rewrite

**Core changes:**
- Abandoned fixed handler functions (`getTagPage()`, `getArticlePage()`, etc.) — replaced with 4 universal page types: `page`, `code`, `paged`, `api`
- URL Pattern auto-compilation — admin enters `/tag/{abc}`, system generates regex `~^/tag/([^/]+)$~` with vars `['abc']`
- Code blocks stored as standalone files (`tpl/code_{key}.log`), edited via File Editor
- `{code:xxx}` placeholder system — code block variables auto-exposed for Content editor reference
- Route status changed to 3 states: 2=Active, 1=Paused, 0=Delete
- Single Save button (no more Save Draft + Activate two-step)
- Unified page editor — all 4 types managed in one place

**New files:**
- `tpl/code_tag.log`, `tpl/code_search.log`, `tpl/code_article.log`, `tpl/code_category.log`, `tpl/code_news.log`, `tpl/code_info.log` — Auto-generated code block files for existing dynamic pages

**Modified files:**
- `inc/site_functions.php` — Added `compileRoutePattern()`, updated `matchRoute()` with `named_params`, removed all `get*Page()` functions and `renderDynamicTemplate()`
- `inc/site_router.php` — Simplified to `['match', 'key', 'vars']` format
- `inc/site_pages.php` — Unified format with `type` field, removed `path`/`before_code`
- `index.php` — New dispatch: API→include code file+exit, code/paged→include code+`{code:xxx}` replacement
- `adm/router.php` — Complete rewrite: URL Pattern input, 3-state status dropdown, single Save button, auto-compile
- `adm/pages.php` — Rewritten as pure Content editor, read-only code block display
- `adm/nav.php` — Tab label changed to "Menu"
- `adm/inc_menu.log` — Updated menu entries: Pages/Content/Menu

**Renamed files:**
- `inc/site_dynamic.php` → `-inc_site_dynamic.php` (functionality merged into site_pages.php)

**Infrastructure:**
- `inc/sys_sql.php` — Created as config template (empty params, setup.php writes on install)
- `inc/sys_conn.php` — Created to auto-read sys_sql.php, setup.php creates once, not rewritten

### Additional v0.9.0 Changes

**New files:**
- `tpl/adm_msg.log` — Announcement template, editable via File Editor, displayed in msg.php
- `tpl/code_post.log`, `tpl/code_sub.log` — Code/API examples for new default routes

**Modified files:**
- `adm/msg.php` — Announcements card below Quick Links, reads from `tpl/adm_msg.log`
- `adm/nav.php` — Preview link for active items; hint for `{sys_site_weburl}` absolute URLs
- `adm/router.php` — Preview link (↗) for Active routes
- `adm/pages.php` — Type badge clickable as preview link for Active pages
- `setup.php` — Password hint shows live `getTdayShort()`; Database Config hides without `data/sql.log`; domain field warns against http/www
- `test.php` — Added `getTdayShort()` to Date Formats
- `inc/sys_inc.php` — Added `ico` support, skips `getimagesize()` for ICO
- `adm/sys.php` — Added `ico` to upload/delete whitelist
- `data/site_router.log` — Default routes: index→Paused, removed category/news/info, tag→paged format, added post/sub
- `inc/site_router.php` — Recompiled (only about + terms active)
- `inc/site_pages.php` — Removed category/news/info, added post/sub
- `data/editor_files.log` — Synced with current code files; added `data` to File Editor allowed dirs
- `inc_level.log` — Moved from `adm/` to `data/` (editable via File Editor)

**Deleted files:**
- `tpl/code_category.log`, `tpl/code_news.log`, `tpl/code_info.log` — Removed with their routes

### Migration notes for existing installations:

1. `data/site_router.log` — Format changed from 6-field to 5-field (`status|pattern|key|vars|remark`)
2. `inc/site_pages.php` — Each entry needs `type` field; remove `path` field
3. `inc/site_dynamic.php` content merged into `inc/site_pages.php`
4. Placeholders: `{tagname}` → `{code:tagname}`, `{content}` → `{code:content_html}`
5. Old handler function code must be migrated into `tpl/code_{key}.log` files
6. See `ROADMAP.md` §7 for detailed migration steps

### 重大变更：路由与页面管理重构

**核心变化：**
- 废弃固定 handler 函数（`getTagPage()` 等），改用 4 种通用页面类型：`page`/`code`/`paged`/`api`
- URL Pattern 自动编译：管理员输入 `/tag/{abc}`，系统自动生成正则 + 变量名
- 代码块独立文件存储（`tpl/code_{key}.log`），通过 File Editor 编辑
- `{code:xxx}` 占位符体系：代码块变量自动暴露给 Content 编辑器引用
- 路由状态改为 3 档：2=启用、1=暂停、0=删除
- 单按钮保存（不再分保存草稿 + 生效两步）
- 统一页面编辑器：4 种类型在一个界面管理

**新增文件：**
- 6 个 `tpl/code_*.log` — 现有动态页面的代码块文件

**修改文件：**
- `inc/site_functions.php` — 加 `compileRoutePattern()`，改 `matchRoute()` 支持 `named_params`，删所有 getPage 函数
- `inc/site_router.php` — 简化为 `['match', 'key', 'vars']`
- `inc/site_pages.php` — 统一格式，加 type 字段
- `index.php` — 新分发流程
- `adm/router.php` — 完全重写
- `adm/pages.php` — 重写为纯内容编辑器
- `adm/nav.php` — Tab 改为 Menu

**重命名文件：**
- `inc/site_dynamic.php` → `-inc_site_dynamic.php`

### Additional v0.9.0 Changes

**New files:**
- `tpl/adm_msg.log` — Announcement template, editable via File Editor, displayed in msg.php
- `tpl/code_post.log` — Code example for POST form handling
- `tpl/code_sub.log` — API example for JSON endpoint

**Modified files:**
- `adm/msg.php` — Added Announcements card below Quick Links, reads from `tpl/adm_msg.log`
- `adm/nav.php` — Added preview link for active items; hint for `{sys_site_weburl}` absolute URLs
- `adm/router.php` — Added preview link (↗) for Active routes
- `adm/pages.php` — Type badge now clickable preview link for Active pages
- `setup.php` — Password hint shows live `getTdayShort()` value; domain field warns against http/www prefix; Database Config section hidden when no `data/sql.log`
- `test.php` — Added `getTdayShort()` display in Date Formats section
- `inc/sys_inc.php` — Added `ico` to allowed image extensions, skips `getimagesize()` for ICO
- `adm/sys.php` — Added `ico` to image upload/delete extension whitelist
- `data/site_router.log` — Default routes: index→Paused, removed category/news/info, tag→paged `/tag/{abc}/{page}.html`, added post/sub examples
- `inc/site_router.php` — Recompiled (only about + terms active)
- `inc/site_pages.php` — Removed category/news/info, added post/sub
- `data/editor_files.log` — Synced with current code files; added `data` to File Editor allowed dirs
- `inc_level.log` — Moved from `adm/` to `data/` (editable via File Editor)

**Deleted files:**
- `tpl/code_category.log`, `tpl/code_news.log`, `tpl/code_info.log` — Removed with their routes

## [0.8.4] - 2026-05-11

### Changed Files

- **`inc/sys_inc.php`**
  - Added `uploadImage()` function — Reusable image upload helper with extension, size, and dimension validation.
  - `checkAntiRefresh()` changed to millisecond precision — `time()` → `microtime(true) * 1000`, default interval 1000ms (1s). Error message shows decimal seconds remaining.
  - `showErrorPage()` auto return URL — When `$returnUrl` is empty, defaults to `$_SERVER['REQUEST_URI']` so error pages always show a Refresh link.

- **`adm/router.php`**
  - Fixed Add Static Route button not submitting action — Missing `name="router_action" value="save"` caused new route data to be posted but never processed. Added the missing attributes.
  - Auto-create page entry on Add Static Route — When adding a static route, if the page key doesn't exist in `inc/site_pages.php`, a default entry is automatically created.

- **`adm/pages.php`**
  - Fixed Add New Page wipes all existing pages — The Add form is a separate `<form>` that doesn't include existing page keys. Fixed: when `page_key[]` is empty, load existing pages from file before merging.

- **`adm/sys.php`**
  - New `type=pics` tab — Image upload to `pics/` directory with custom filename, max 10MB, 10-5000px dimensions, supports jpg/jpeg/png/gif/webp/bmp/svg. Image list with thumbnail preview, modified time, open link, and remove button.

- **`test.php`**
  - New Image Upload Environment check section — Tests `getimagesize()`, `file_uploads`, `upload_max_filesize`, `post_max_size`, `pics/` writability, `move_uploaded_file()`.
  - Updated `checkAntiRefresh()` call to 1000ms.

- **`README.md`**
  - test.php description updated — "Test playground" → "System environment checker & function test". Chinese version synced.

### 变更文件

- **`inc/sys_inc.php`**
  - 新增 `uploadImage()` 函数 — 图片上传辅助函数，含扩展名、大小、尺寸校验。
  - `checkAntiRefresh()` 毫秒精度 — 从 `time()`（秒）改为 `microtime(true) * 1000`（毫秒），默认 1000ms（1秒）。错误信息显示小数秒。
  - `showErrorPage()` 自动返回链接 — `$returnUrl` 为空时自动用 `$_SERVER['REQUEST_URI']`，错误页始终显示 Refresh 链接。

- **`adm/router.php`**
  - 修复 Add 按钮未提交 action — 缺少 `name="router_action" value="save"`，导致新路由数据提交后未处理。已补全属性。
  - 添加路由时自动创建页面条目 — 新增静态路由时，自动检查 `inc/site_pages.php` 是否有对应 key，没有则创建默认条目。

- **`adm/pages.php`**
  - 修复 Add New Page 丢失已有页面 — Add 表单不包含已有页面字段，修复：`page_key[]` 为空时先加载已有文件再合并。

- **`adm/sys.php`**
  - 新增 `type=pics` 标签页 — 上传图片到 `pics/` 目录，支持自定义文件名、最大10MB、10-5000px、jpg/jpeg/png/gif/webp/bmp/svg。图片列表带缩略图、修改时间、打开链接和删除按钮。

- **`test.php`**
  - 新增 Image Upload Environment 检测区块 — 检测 `getimagesize()`、`file_uploads`、上传大小限制、`pics/` 可写性等运行环境。
  - 更新 `checkAntiRefresh()` 调用为 1000ms。

- **`README.md`**
  - test.php 描述更新 — "Test playground" → "System environment checker & function test"，中文同步。
