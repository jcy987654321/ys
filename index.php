<?php
// 启动Session
session_start();

// 新增：检查是否需要从文件重新加载配置
if (isset($_GET['reload_config']) && $_GET['reload_config'] === '1') {
    unset($_SESSION['custom_api_sources']);
    unset($_SESSION['parser_config']);
    unset($_SESSION['ad_removal_config']);
    // 强制重新初始化
    if (file_exists('custom_sources.json')) {
        $sources_data = file_get_contents('custom_sources.json');
        $_SESSION['custom_api_sources'] = json_decode($sources_data, true) ?: [];
    }
    if (file_exists('parser_config.json')) {
        $parser_data = file_get_contents('parser_config.json');
        $_SESSION['parser_config'] = json_decode($parser_data, true) ?: [];
    }
    if (file_exists('ad_removal_config.json')) {
        $ad_removal_data = file_get_contents('ad_removal_config.json');
        $_SESSION['ad_removal_config'] = json_decode($ad_removal_data, true) ?: [];
    }
    header('Location: ?admin=1&msg=配置已重新加载');
    exit;
}

// 配置信息
define('API_BASE_URL', 'https://cj.lziapi.com/api.php/provide/vod/');
define('DYTT_API_BASE_URL', 'http://caiji.dyttzyapi.com/api.php/provide/vod/');
define('SITE_NAME', '月下独酌影视');
define('ITEMS_PER_PAGE', 20);
define('DOWNLOAD_ITEMS_PER_PAGE', 20); // 下载资源每页显示数量
define('DEFAULT_IMAGE', 'https://t.alcy.cc/moemp?text=');
define('SITE_NOTICE', '青山数据源为高清资源，记得选择选择解析器为青山，温馨提示：视频都来源于网络，电影天堂为无广告资源，请勿相信视频内任何广告内容。');
define('ADMIN_PASSWORD', 'jcy666nb');
define('CUSTOM_SOURCES_FILE', 'custom_sources.json');
define('PARSER_CONFIG_FILE', 'parser_config.json');
define('AD_REMOVAL_CONFIG_FILE', 'ad_removal_config.json'); // 新增：去插播接口配置文件

// 初始化自定义API源 - 改进版本
function init_custom_api_sources() {
    $file_path = CUSTOM_SOURCES_FILE;
    $session_key = 'custom_api_sources';
    $file_mtime_key = 'custom_api_sources_mtime';
    
    // 检查文件修改时间，如果文件已更新则重新加载
    if (file_exists($file_path)) {
        $current_mtime = filemtime($file_path);
        $stored_mtime = isset($_SESSION[$file_mtime_key]) ? $_SESSION[$file_mtime_key] : 0;
        
        if (!isset($_SESSION[$session_key]) || $current_mtime > $stored_mtime) {
            $sources_data = file_get_contents($file_path);
            $_SESSION[$session_key] = json_decode($sources_data, true) ?: [];
            $_SESSION[$file_mtime_key] = $current_mtime;
        }
    } else {
        $_SESSION[$session_key] = [];
    }
    
    return $_SESSION[$session_key];
}

// 保存自定义API源到文件
function save_custom_api_sources($sources) {
    $_SESSION['custom_api_sources'] = $sources;
    $_SESSION['custom_api_sources_mtime'] = time(); // 更新修改时间
    file_put_contents(CUSTOM_SOURCES_FILE, json_encode($sources, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

// 初始化解析器配置 - 改进版本
function init_parser_config() {
    $file_path = PARSER_CONFIG_FILE;
    $session_key = 'parser_config';
    $file_mtime_key = 'parser_config_mtime';
    
    // 检查文件修改时间，如果文件已更新则重新加载
    if (file_exists($file_path)) {
        $current_mtime = filemtime($file_path);
        $stored_mtime = isset($_SESSION[$file_mtime_key]) ? $_SESSION[$file_mtime_key] : 0;
        
        if (!isset($_SESSION[$session_key]) || $current_mtime > $stored_mtime) {
            $parser_data = file_get_contents($file_path);
            $_SESSION[$session_key] = json_decode($parser_data, true) ?: [];
            $_SESSION[$file_mtime_key] = $current_mtime;
        }
    } else {
        $_SESSION[$session_key] = [];
    }
    
    return $_SESSION[$session_key];
}

// 保存解析器配置到文件
function save_parser_config($config) {
    $_SESSION['parser_config'] = $config;
    $_SESSION['parser_config_mtime'] = time(); // 更新修改时间
    file_put_contents(PARSER_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

// 新增：初始化去插播接口配置
function init_ad_removal_config() {
    $file_path = AD_REMOVAL_CONFIG_FILE;
    $session_key = 'ad_removal_config';
    $file_mtime_key = 'ad_removal_config_mtime';
    
    // 检查文件修改时间，如果文件已更新则重新加载
    if (file_exists($file_path)) {
        $current_mtime = filemtime($file_path);
        $stored_mtime = isset($_SESSION[$file_mtime_key]) ? $_SESSION[$file_mtime_key] : 0;
        
        if (!isset($_SESSION[$session_key]) || $current_mtime > $stored_mtime) {
            $ad_removal_data = file_get_contents($file_path);
            $_SESSION[$session_key] = json_decode($ad_removal_data, true) ?: [];
            $_SESSION[$file_mtime_key] = $current_mtime;
        }
    } else {
        // 如果没有配置文件，设置默认的去插播接口
        $_SESSION[$session_key] = [
            'default' => [
                'name' => '默认去插播接口',
                'url' => 'https://jx.dmcdn.top/?url=',
                'enabled' => true,
                'admin_only' => false,
                'for_all_sources' => true,
                'assigned_sources' => []
            ]
        ];
    }
    
    return $_SESSION[$session_key];
}

// 新增：保存去插播接口配置到文件
function save_ad_removal_config($config) {
    $_SESSION['ad_removal_config'] = $config;
    $_SESSION['ad_removal_config_mtime'] = time(); // 更新修改时间
    file_put_contents(AD_REMOVAL_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

// API源配置
$api_sources = [
    'default' => [
        'name' => '量子源',
        'base_url' => API_BASE_URL,
        'admin_only' => false,
        'type' => 'standard' // standard, tvbox
    ],
    'dytt' => [
        'name' => '电影天堂源',
        'base_url' => DYTT_API_BASE_URL,
        'admin_only' => false,
        'type' => 'standard'
    ]
];

// 初始化自定义API源和解析器配置
init_custom_api_sources();
init_parser_config();
init_ad_removal_config(); // 新增：初始化去插播接口配置

// 设置当前API源
if (!isset($_SESSION['current_api_source'])) {
    $_SESSION['current_api_source'] = 'default';
}

// 获取所有可用的API源（根据管理员状态过滤）
function get_available_api_sources() {
    global $api_sources;
    $custom_sources = init_custom_api_sources();
    $all_sources = array_merge($api_sources, $custom_sources);
    
    // 如果不是管理员，过滤掉管理员专用源
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        foreach ($all_sources as $key => $source) {
            if (isset($source['admin_only']) && $source['admin_only']) {
                unset($all_sources[$key]);
            }
        }
    }
    
    return $all_sources;
}

$available_sources = get_available_api_sources();

if (isset($_GET['api_source']) && array_key_exists($_GET['api_source'], $available_sources)) {
    $_SESSION['current_api_source'] = $_GET['api_source'];
}

function get_current_api_base_url() {
    $available_sources = get_available_api_sources();
    
    if (isset($available_sources[$_SESSION['current_api_source']])) {
        return $available_sources[$_SESSION['current_api_source']]['base_url'];
    }
    
    return API_BASE_URL;
}

// 处理管理员登录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    if (isset($_POST['password']) && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: ?admin=1');
        exit;
    } else {
        $login_error = "密码错误";
    }
}

// 处理管理员退出
if (isset($_GET['action']) && $_GET['action'] === 'admin_logout') {
    // 如果当前源是管理员专用源，切换回默认源
    $current_source = $_SESSION['current_api_source'];
    $available_sources = get_available_api_sources();
    
    if (isset($available_sources[$current_source]) && 
        isset($available_sources[$current_source]['admin_only']) && 
        $available_sources[$current_source]['admin_only']) {
        $_SESSION['current_api_source'] = 'default';
    }
    
    unset($_SESSION['admin_logged_in']);
    header('Location: ?');
    exit;
}

// 处理添加API源的POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_api_source' && isset($_SESSION['admin_logged_in'])) {
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $admin_only = isset($_POST['admin_only']) ? true : false;
    $type = isset($_POST['type']) ? $_POST['type'] : 'standard';
    $key = 'custom_' . uniqid();
    
    if (!empty($name) && !empty($url)) {
        $custom_sources = init_custom_api_sources();
        
        $custom_sources[$key] = [
            'name' => $name,
            'base_url' => $url,
            'admin_only' => $admin_only,
            'type' => $type
        ];
        
        save_custom_api_sources($custom_sources);
        
        header('Location: ?admin=1&msg=API源添加成功');
        exit;
    }
}

// 处理编辑API源的POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_api_source' && isset($_SESSION['admin_logged_in'])) {
    $key = $_POST['key'];
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $admin_only = isset($_POST['admin_only']) ? true : false;
    $type = isset($_POST['type']) ? $_POST['type'] : 'standard';
    
    if (!empty($name) && !empty($url)) {
        $custom_sources = init_custom_api_sources();
        
        if (isset($custom_sources[$key])) {
            $custom_sources[$key] = [
                'name' => $name,
                'base_url' => $url,
                'admin_only' => $admin_only,
                'type' => $type
            ];
            
            save_custom_api_sources($custom_sources);
            
            header('Location: ?admin=1&msg=API源编辑成功');
            exit;
        }
    }
}

// 处理删除API源的请求
if (isset($_GET['action']) && $_GET['action'] === 'delete_api_source' && isset($_GET['key']) && isset($_SESSION['admin_logged_in'])) {
    $custom_sources = init_custom_api_sources();
    
    if (isset($custom_sources[$_GET['key']])) {
        // 如果删除的是当前正在使用的源，切换回默认源
        if ($_SESSION['current_api_source'] === $_GET['key']) {
            $_SESSION['current_api_source'] = 'default';
        }
        
        unset($custom_sources[$_GET['key']]);
        save_custom_api_sources($custom_sources);
        
        header('Location: ?admin=1&msg=API源删除成功');
        exit;
    }
}

// 处理添加解析器的POST请求 - 修复版本
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_parser' && isset($_SESSION['admin_logged_in'])) {
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $enabled = isset($_POST['enabled']) ? true : false;
    $for_all_sources = isset($_POST['for_all_sources']) ? true : false;
    $admin_only = isset($_POST['admin_only']) ? true : false;
    
    // 修复：正确处理分配的数据源
    $assigned_sources = [];
    if (!$for_all_sources && isset($_POST['assigned_sources']) && is_array($_POST['assigned_sources'])) {
        $assigned_sources = $_POST['assigned_sources'];
    }
    
    $key = 'parser_' . uniqid();
    
    if (!empty($name) && !empty($url)) {
        $parser_config = init_parser_config();
        
        $parser_config[$key] = [
            'name' => $name,
            'url' => $url,
            'enabled' => $enabled,
            'for_all_sources' => $for_all_sources,
            'admin_only' => $admin_only,
            'assigned_sources' => $assigned_sources // 确保这里保存的是数组
        ];
        
        save_parser_config($parser_config);
        
        header('Location: ?admin=1&msg=解析器添加成功');
        exit;
    }
}

// 处理编辑解析器的POST请求 - 修复版本
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_parser' && isset($_SESSION['admin_logged_in'])) {
    $key = $_POST['key'];
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $enabled = isset($_POST['enabled']) ? true : false;
    $for_all_sources = isset($_POST['for_all_sources']) ? true : false;
    $admin_only = isset($_POST['admin_only']) ? true : false;
    
    // 修复：正确处理分配的数据源
    $assigned_sources = [];
    if (!$for_all_sources && isset($_POST['assigned_sources']) && is_array($_POST['assigned_sources'])) {
        $assigned_sources = $_POST['assigned_sources'];
    }
    
    if (!empty($name) && !empty($url)) {
        $parser_config = init_parser_config();
        
        if (isset($parser_config[$key])) {
            $parser_config[$key] = [
                'name' => $name,
                'url' => $url,
                'enabled' => $enabled,
                'for_all_sources' => $for_all_sources,
                'admin_only' => $admin_only,
                'assigned_sources' => $assigned_sources // 确保这里保存的是数组
            ];
            
            save_parser_config($parser_config);
            
            header('Location: ?admin=1&msg=解析器编辑成功');
            exit;
        }
    }
}

// 处理删除解析器的请求
if (isset($_GET['action']) && $_GET['action'] === 'delete_parser' && isset($_GET['key']) && isset($_SESSION['admin_logged_in'])) {
    $parser_config = init_parser_config();
    
    if (isset($parser_config[$_GET['key']])) {
        unset($parser_config[$_GET['key']]);
        save_parser_config($parser_config);
        
        header('Location: ?admin=1&msg=解析器删除成功');
        exit;
    }
}

// 处理切换解析器状态的请求
if (isset($_GET['action']) && $_GET['action'] === 'toggle_parser' && isset($_GET['key']) && isset($_SESSION['admin_logged_in'])) {
    $parser_config = init_parser_config();
    
    if (isset($parser_config[$_GET['key']])) {
        $parser_config[$_GET['key']]['enabled'] = !$parser_config[$_GET['key']]['enabled'];
        save_parser_config($parser_config);
        
        header('Location: ?admin=1&msg=解析器状态已更新');
        exit;
    }
}

// 新增：处理添加去插播接口的POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ad_removal' && isset($_SESSION['admin_logged_in'])) {
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $enabled = isset($_POST['enabled']) ? true : false;
    $for_all_sources = isset($_POST['for_all_sources']) ? true : false;
    $admin_only = isset($_POST['admin_only']) ? true : false;
    
    // 正确处理分配的数据源
    $assigned_sources = [];
    if (!$for_all_sources && isset($_POST['assigned_sources']) && is_array($_POST['assigned_sources'])) {
        $assigned_sources = $_POST['assigned_sources'];
    }
    
    $key = 'ad_removal_' . uniqid();
    
    if (!empty($name) && !empty($url)) {
        $ad_removal_config = init_ad_removal_config();
        
        $ad_removal_config[$key] = [
            'name' => $name,
            'url' => $url,
            'enabled' => $enabled,
            'for_all_sources' => $for_all_sources,
            'admin_only' => $admin_only,
            'assigned_sources' => $assigned_sources
        ];
        
        save_ad_removal_config($ad_removal_config);
        
        header('Location: ?admin=1&msg=去插播接口添加成功');
        exit;
    }
}

// 新增：处理编辑去插播接口的POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_ad_removal' && isset($_SESSION['admin_logged_in'])) {
    $key = $_POST['key'];
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $enabled = isset($_POST['enabled']) ? true : false;
    $for_all_sources = isset($_POST['for_all_sources']) ? true : false;
    $admin_only = isset($_POST['admin_only']) ? true : false;
    
    // 正确处理分配的数据源
    $assigned_sources = [];
    if (!$for_all_sources && isset($_POST['assigned_sources']) && is_array($_POST['assigned_sources'])) {
        $assigned_sources = $_POST['assigned_sources'];
    }
    
    if (!empty($name) && !empty($url)) {
        $ad_removal_config = init_ad_removal_config();
        
        if (isset($ad_removal_config[$key])) {
            $ad_removal_config[$key] = [
                'name' => $name,
                'url' => $url,
                'enabled' => $enabled,
                'for_all_sources' => $for_all_sources,
                'admin_only' => $admin_only,
                'assigned_sources' => $assigned_sources
            ];
            
            save_ad_removal_config($ad_removal_config);
            
            header('Location: ?admin=1&msg=去插播接口编辑成功');
            exit;
        }
    }
}

// 新增：处理删除去插播接口的请求
if (isset($_GET['action']) && $_GET['action'] === 'delete_ad_removal' && isset($_GET['key']) && isset($_SESSION['admin_logged_in'])) {
    $ad_removal_config = init_ad_removal_config();
    
    if (isset($ad_removal_config[$_GET['key']])) {
        unset($ad_removal_config[$_GET['key']]);
        save_ad_removal_config($ad_removal_config);
        
        header('Location: ?admin=1&msg=去插播接口删除成功');
        exit;
    }
}

// 新增：处理切换去插播接口状态的请求
if (isset($_GET['action']) && $_GET['action'] === 'toggle_ad_removal' && isset($_GET['key']) && isset($_SESSION['admin_logged_in'])) {
    $ad_removal_config = init_ad_removal_config();
    
    if (isset($ad_removal_config[$_GET['key']])) {
        $ad_removal_config[$_GET['key']]['enabled'] = !$ad_removal_config[$_GET['key']]['enabled'];
        save_ad_removal_config($ad_removal_config);
        
        header('Location: ?admin=1&msg=去插播接口状态已更新');
        exit;
    }
}

// 合并自定义API源到主源数组
$custom_sources = init_custom_api_sources();
$api_sources = array_merge($api_sources, $custom_sources);

// 获取当前可用的解析器 - 修复版本
function get_available_parsers($source_key = null) {
    $parser_config = init_parser_config();
    $available_parsers = [];
    
    foreach ($parser_config as $key => $parser) {
        // 跳过未启用的解析器
        if (!$parser['enabled']) continue;
        
        // 如果是管理员专用解析器且用户不是管理员，跳过
        if (isset($parser['admin_only']) && $parser['admin_only'] && (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in'])) {
            continue;
        }
        
        // 如果解析器适用于所有源，或者专门分配给当前源
        if ($parser['for_all_sources'] || 
            (isset($parser['assigned_sources']) && is_array($parser['assigned_sources']) && 
             in_array($source_key, $parser['assigned_sources']))) {
            $available_parsers[$key] = $parser;
        }
    }
    
    return $available_parsers;
}

// 新增：获取当前可用的去插播接口
function get_available_ad_removals($source_key = null) {
    $ad_removal_config = init_ad_removal_config();
    $available_ad_removals = [];
    
    foreach ($ad_removal_config as $key => $ad_removal) {
        // 跳过未启用的去插播接口
        if (!$ad_removal['enabled']) continue;
        
        // 如果是管理员专用去插播接口且用户不是管理员，跳过
        if (isset($ad_removal['admin_only']) && $ad_removal['admin_only'] && (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in'])) {
            continue;
        }
        
        // 如果去插播接口适用于所有源，或者专门分配给当前源
        if ($ad_removal['for_all_sources'] || 
            (isset($ad_removal['assigned_sources']) && is_array($ad_removal['assigned_sources']) && 
             in_array($source_key, $ad_removal['assigned_sources']))) {
            $available_ad_removals[$key] = $ad_removal;
        }
    }
    
    return $available_ad_removals;
}

// 处理添加历史记录的POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_history') {
    header('Content-Type: application/json');
    
    if (!isset($_POST['vod_id']) || !isset($_POST['vod_name']) || !isset($_POST['episode_name']) || !isset($_POST['episode_url']) || !isset($_POST['vod_pic'])) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    $vod_id = intval($_POST['vod_id']);
    $vod_name = $_POST['vod_name'];
    $episode_name = $_POST['episode_name'];
    $episode_url = $_POST['episode_url'];
    $vod_pic = $_POST['vod_pic'];
    
    // 添加到观看历史
    add_to_history($vod_id, $vod_name, $episode_name, $episode_url, $vod_pic);
    
    echo json_encode(['success' => true]);
    exit;
}

// 初始化历史记录
if (!isset($_SESSION['watch_history'])) {
    $_SESSION['watch_history'] = [];
}

// 添加到观看历史
function add_to_history($vod_id, $vod_name, $episode_name, $episode_url, $vod_pic = '') {
    global $api_sources;
    
    $new_record = [
        'id' => $vod_id,
        'name' => $vod_name,
        'episode' => $episode_name,
        'url' => $episode_url,
        'pic' => $vod_pic,
        'timestamp' => time(),
        'source' => $_SESSION['current_api_source'],
        'source_name' => isset($api_sources[$_SESSION['current_api_source']]) ? $api_sources[$_SESSION['current_api_source']]['name'] : '未知源'
    ];
    
    foreach ($_SESSION['watch_history'] as $index => $record) {
        if ($record['id'] == $vod_id && $record['episode'] == $episode_name) {
            unset($_SESSION['watch_history'][$index]);
            break;
        }
    }
    
    array_unshift($_SESSION['watch_history'], $new_record);
    
    if (count($_SESSION['watch_history']) > 50) {
        $_SESSION['watch_history'] = array_slice($_SESSION['watch_history'], 0, 50);
    }
}

// 获取分类信息（移除缓存）
function get_categories() {
    $url = get_current_api_base_url();
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === FALSE) {
        return [];
    }
    
    $data = json_decode($response, true);
    
    // 检查是否是TVBox格式
    $current_source = get_current_api_source_info();
    if (isset($current_source['type']) && $current_source['type'] === 'tvbox') {
        // TVBox格式处理
        $categories = [];
        if (isset($data['class'])) {
            foreach ($data['class'] as $category) {
                $categories[] = [
                    'type_id' => $category['type_id'] ?? 0,
                    'type_name' => $category['type_name'] ?? '未知'
                ];
            }
        }
        return $categories;
    } else {
        // 标准格式处理
        $categories = isset($data['class']) ? $data['class'] : [];
        return $categories;
    }
}

// 获取当前API源信息
function get_current_api_source_info() {
    $available_sources = get_available_api_sources();
    return isset($available_sources[$_SESSION['current_api_source']]) ? $available_sources[$_SESSION['current_api_source']] : null;
}

// 获取影片列表（移除缓存）
function get_vod_list($page = 1, $type_id = 0) {
    $current_source = get_current_api_source_info();
    $url = get_current_api_base_url();
    
    // 根据API源类型构建不同的参数
    if (isset($current_source['type']) && $current_source['type'] === 'tvbox') {
        // TVBox格式
        $params = ['ac' => 'detail'];
        if ($type_id > 0) $params['t'] = $type_id;
        if ($page > 1) $params['pg'] = $page;
    } else {
        // 标准格式
        $params = ['ac' => 'detail', 'pg' => $page];
        if ($type_id > 0) $params['t'] = $type_id;
    }
    
    $url = $url . '?' . http_build_query($params);
    $context = stream_context_create([
        'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === FALSE) return ['list' => [], 'total' => 0, 'pagecount' => 1];
    
    $data = json_decode($response, true);
    
    // 处理TVBox格式数据
    if (isset($current_source['type']) && $current_source['type'] === 'tvbox') {
        if (isset($data['list'])) {
            return [
                'list' => $data['list'],
                'total' => count($data['list']),
                'pagecount' => 1,
                'limit' => 1000
            ];
        } else {
            return ['list' => [], 'total' => 0, 'pagecount' => 1];
        }
    } else {
        // 标准格式
        return $data;
    }
}

// 获取影片详情（移除缓存）
function get_vod_detail($vod_id) {
    $current_source = get_current_api_source_info();
    $url = get_current_api_base_url();
    
    // 根据API源类型构建不同的参数
    if (isset($current_source['type']) && $current_source['type'] === 'tvbox') {
        // TVBox格式通常使用ids参数
        $url = $url . '?ac=detail&ids=' . $vod_id;
    } else {
        // 标准格式
        $url = $url . '?ac=detail&ids=' . $vod_id;
    }
    
    $context = stream_context_create([
        'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === FALSE) return null;
    
    $data = json_decode($response, true);
    
    if (isset($data['list'][0])) {
        return $data['list'][0];
    }
    
    return null;
}

// 搜索影片
function search_vod($keyword, $page = 1) {
    $current_source = get_current_api_source_info();
    $url = get_current_api_base_url();
    
    // 根据API源类型构建不同的参数
    if (isset($current_source['type']) && $current_source['type'] === 'tvbox') {
        // TVBox格式
        $params = ['ac' => 'detail', 'wd' => $keyword];
        if ($page > 1) $params['pg'] = $page;
    } else {
        // 标准格式
        $params = ['ac' => 'detail', 'wd' => $keyword, 'pg' => $page];
    }
    
    $url = $url . '?' . http_build_query($params);
    
    $context = stream_context_create([
        'http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === FALSE) return ['list' => [], 'total' => 0, 'pagecount' => 1];
    
    $data = json_decode($response, true);
    
    // 处理TVBox格式数据
    if (isset($current_source['type']) && $current_source['type'] === 'tvbox') {
        if (isset($data['list'])) {
            return [
                'list' => $data['list'],
                'total' => count($data['list']),
                'pagecount' => 1
            ];
        } else {
            return ['list' => [], 'total' => 0, 'pagecount' => 1];
        }
    } else {
        // 标准格式
        return $data;
    }
}

// 解析播放列表
function parse_play_list($play_from, $play_url) {
    $sources = explode('$$$', $play_from);
    $urls = explode('$$$', $play_url);
    $result = [];
    
    for ($i = 0; $i < count($sources); $i++) {
        $source = $sources[$i];
        $episodes = [];
        
        if (isset($urls[$i])) {
            $episode_list = explode('#', $urls[$i]);
            foreach ($episode_list as $episode) {
                $parts = explode('$', $episode, 2);
                if (count($parts) == 2) {
                    $episodes[] = ['name' => $parts[0], 'url' => $parts[1]];
                }
            }
        }
        
        $result[] = ['source' => $source, 'episodes' => $episodes];
    }
    
    return $result;
}

// 解析下载列表
function parse_down_list($down_from, $down_url) {
    $sources = explode('$$$', $down_from);
    $urls = explode('$$$', $down_url);
    $result = [];
    
    for ($i = 0; $i < count($sources); $i++) {
        $source = $sources[$i];
        $episodes = [];
        
        if (isset($urls[$i])) {
            $episode_list = explode('#', $urls[$i]);
            foreach ($episode_list as $episode) {
                $parts = explode('$', $episode, 2);
                if (count($parts) == 2) {
                    $episodes[] = ['name' => $parts[0], 'url' => $parts[1]];
                }
            }
        }
        
        $result[] = ['source' => $source, 'episodes' => $episodes];
    }
    
    return $result;
}

// 清空观看历史
function clear_history() {
    $_SESSION['watch_history'] = [];
}

// 获取请求参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$type_id = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$vod_id = isset($_GET['vod_id']) ? intval($_GET['vod_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$admin = isset($_GET['admin']) ? $_GET['admin'] : 0;
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// 处理清空历史请求
if ($action === 'clear_history') {
    clear_history();
    header('Location: ?action=history');
    exit;
}

// 路由处理
if ($action === 'history') {
    $show_history = true;
} else if ($vod_id > 0) {
    $vod_detail = get_vod_detail($vod_id);
    if (!$vod_detail) {
        header('HTTP/1.1 404 Not Found');
        die('影片不存在');
    }
} else if (!empty($keyword)) {
    $search_result = search_vod($keyword, $page);
    $vod_list = isset($search_result['list']) ? $search_result['list'] : [];
    $total = isset($search_result['total']) ? $search_result['total'] : 0;
    $pagecount = isset($search_result['pagecount']) ? $search_result['pagecount'] : 1;
} else {
    $vod_data = get_vod_list($page, $type_id);
    $vod_list = isset($vod_data['list']) ? $vod_data['list'] : [];
    $total = isset($vod_data['total']) ? $vod_data['total'] : 0;
    $pagecount = isset($vod_data['pagecount']) ? $vod_data['pagecount'] : 1;
}

// 获取分类
$categories = get_categories();

// 解析播放和下载列表
if ($vod_id > 0 && isset($vod_detail)) {
    $down_list = [];
    if (!empty($vod_detail['vod_down_from']) && !empty($vod_detail['vod_down_url'])) {
        $down_list = parse_down_list($vod_detail['vod_down_from'], $vod_detail['vod_down_url']);
    }
    
    if (!empty($vod_detail['vod_play_from']) && !empty($vod_detail['vod_play_url'])) {
        $play_list = parse_play_list($vod_detail['vod_play_from'], $vod_detail['vod_play_url']);
    }
}

// 获取当前可用的解析器
$available_parsers = get_available_parsers($_SESSION['current_api_source']);

// 新增：获取当前可用的去插播接口
$available_ad_removals = get_available_ad_removals($_SESSION['current_api_source']);

// 生成版本号用于缓存清除
$version = '1.4.1'; // 更新版本号

// 处理解析器表单的初始状态
$parser_form = [
    'for_all_sources' => true,
    'assigned_sources' => []
];

// 动态生成页面标题
$page_title = SITE_NAME;
if ($vod_id > 0 && isset($vod_detail)) {
    $page_title = htmlspecialchars($vod_detail['vod_name']) . ' - ' . SITE_NAME;
} elseif (!empty($keyword)) {
    $page_title = '搜索：' . htmlspecialchars($keyword) . ' - ' . SITE_NAME;
} elseif ($type_id > 0) {
    $type_name = '';
    foreach ($categories as $cat) {
        if ($cat['type_id'] == $type_id) {
            $type_name = $cat['type_name'];
            break;
        }
    }
    $page_title = htmlspecialchars($type_name) . ' - ' . SITE_NAME;
} elseif (isset($show_history) && $show_history) {
    $page_title = '观看历史 - ' . SITE_NAME;
} elseif ($admin) {
    $page_title = '管理后台 - ' . SITE_NAME;
} else {
    $page_title = SITE_NAME . ' - 高清影视在线观看';
}

// 动态生成页面描述
$page_description = '月下独酌影视提供最新高清电影、电视剧在线观看，海量视频资源免费观看';
if ($vod_id > 0 && isset($vod_detail)) {
    $page_description = htmlspecialchars(mb_substr($vod_detail['vod_blurb'], 0, 150, 'UTF-8'));
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- 禁用缓存 -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- 新UI的CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* 修复后的CSS样式 - 包含修复的下载资源分页和手机端数据源选择样式 */
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --background-color: #f8f9fa;
            --card-color: #ffffff;
            --text-color: #333333;
            --text-light: #666666;
            --border-color: #e1e5e9;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* 头部样式 */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 20px;
            position: sticky;
            top: 0;
            background: var(--background-color);
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--shadow);
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .datetime {
            text-align: right;
            color: var(--text-light);
        }
        
        .date {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .time {
            font-size: 14px;
        }
        
        /* 搜索框样式 */
        .search-container {
            margin-bottom: 20px;
        }
        
        .search-box {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            background: var(--card-color);
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        
        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* 快速导航按钮 */
        .quick-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 8px 16px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            text-decoration: none;
            color: var(--text-color);
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .nav-btn:hover, .nav-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* 影片网格 */
        .vod-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .vod-card {
            background: var(--card-color);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .vod-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .vod-poster {
            width: 100%;
            height: 240px;
            position: relative;
            overflow: hidden;
        }
        
        .vod-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .vod-card:hover .vod-poster img {
            transform: scale(1.05);
        }
        
        .vod-info {
            padding: 12px;
        }
        
        .vod-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .vod-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-light);
            font-size: 12px;
        }
        
        /* 详情页样式 */
        .detail-container {
            background: var(--card-color);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        
        .detail-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-poster {
            width: 200px;
            height: 280px;
            border-radius: var(--radius);
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: var(--shadow);
        }
        
        .detail-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .detail-content {
            flex: 1;
        }
        
        .detail-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .detail-subtitle {
            color: var(--text-light);
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-light);
            font-size: 14px;
        }
        
        .meta-item i {
            color: var(--primary-color);
        }
        
        .detail-description {
            line-height: 1.6;
            color: var(--text-color);
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* 修复：去插播接口选择器样式 */
        .ad-removal-selector {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
            flex-wrap: wrap;
            background: rgba(46, 204, 113, 0.05);
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        
        .ad-removal-selector label {
            font-weight: 500;
            color: var(--text-color);
            margin: 0;
        }
        
        .ad-removal-selector select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--card-color);
            color: var(--text-color);
            font-size: 14px;
            min-width: 200px;
        }
        
        .ad-removal-info {
            color: var(--text-light);
            font-size: 13px;
            margin-left: 10px;
        }
        
        /* 剧集列表和搜索功能 */
        .episode-section {
            margin-top: 20px;
        }
        
        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .episode-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .episode-search {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        
        .episode-search-input {
            width: 100%;
            padding: 8px 15px;
            padding-right: 35px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            background: var(--card-color);
            font-size: 14px;
        }
        
        .episode-search-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .episode-search-clear {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 16px;
            display: none;
        }
        
        .episode-search-clear.show {
            display: block;
        }
        
        .episode-pagination {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .episode-pagination-btn {
            padding: 8px 12px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }
        
        .episode-pagination-btn:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .episode-pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .episode-pagination-info {
            color: var(--text-light);
            font-size: 13px;
            margin: 0 5px;
        }
        
        .episode-group-selector {
            padding: 8px 12px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-color);
            cursor: pointer;
            font-size: 13px;
        }
        
        .episode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px;
        }
        
        .episode-btn {
            padding: 10px 8px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-color);
            text-decoration: none;
            font-size: 13px;
        }
        
        .episode-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        /* 修复：下载资源样式 */
        .download-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .download-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .episode-download {
            padding: 10px 8px;
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            color: #3498db;
            text-decoration: none;
            display: block;
        }
        
        .episode-download:hover {
            background: rgba(52, 152, 219, 0.2);
            color: #2980b9;
            transform: translateY(-2px);
        }
        
        /* 修复：下载资源搜索和分页控件样式 */
        .download-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
            background: rgba(52, 152, 219, 0.05);
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .download-search {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        
        .download-search-input {
            width: 100%;
            padding: 8px 15px;
            padding-right: 35px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            background: var(--card-color);
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .download-search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }
        
        .download-search-clear {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 16px;
            display: none;
        }
        
        .download-search-clear:hover {
            color: var(--primary-color);
        }
        
        .download-pagination {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .download-pagination-btn {
            padding: 8px 12px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }
        
        .download-pagination-btn:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }
        
        .download-pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .download-pagination-info {
            color: var(--text-light);
            font-size: 13px;
            margin: 0 5px;
            min-width: 120px;
            text-align: center;
        }
        
        .download-group-selector {
            padding: 8px 12px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-color);
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .download-group-selector:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        /* 修复：下载项悬停效果增强 */
        .episode-download {
            transition: all 0.3s ease !important;
            position: relative;
            overflow: hidden;
        }
        
        .episode-download::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .episode-download:hover::before {
            left: 100%;
        }
        
        /* 分页样式 */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .page-link {
            padding: 8px 14px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .page-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* 跳转页面输入框 */
        .page-jump {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 15px;
        }
        
        .page-jump-input {
            width: 60px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
        }
        
        .page-jump-btn {
            padding: 8px 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        /* 管理界面样式 */
        .admin-panel {
            background: var(--card-color);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        
        .admin-header {
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-form {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr auto;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .form-input {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-color);
            font-size: 14px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-btn {
            padding: 10px 16px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* 修复：解析器表单样式 - 优化手机端显示 */
        .parser-form-container {
            margin-bottom: 25px;
        }
        
        .parser-form {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .source-assignment {
            grid-column: 1 / -1;
            margin-top: 10px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            display: none;
        }
        
        .source-assignment.show {
            display: block;
        }
        
        .source-assignment label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .sources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
        }
        
        .source-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .source-checkbox input {
            margin: 0;
        }
        
        .source-checkbox label {
            margin: 0;
            font-weight: normal;
        }
        
        /* 一键下滑按钮 */
        .scroll-down-btn {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 99;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }
        
        .scroll-down-btn.show {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-down-btn:hover {
            transform: translateY(-5px);
        }
        
        /* 返回顶部按钮 */
        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 99;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }
        
        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-to-top:hover {
            transform: translateY(-5px);
        }
        
        /* 修复：响应式设计 - 优化手机端数据源选择 */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .detail-header {
                flex-direction: column;
            }
            
            .detail-poster {
                width: 100%;
                max-width: 200px;
                margin: 0 auto 15px;
            }
            
            .vod-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 15px;
            }
            
            .admin-form, .parser-form {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .quick-nav {
                justify-content: center;
            }
            
            .episode-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .episode-search {
                min-width: auto;
            }
            
            /* 修复：手机端数据源选择网格布局 */
            .sources-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .source-checkbox {
                padding: 8px;
                background: rgba(0, 0, 0, 0.02);
                border-radius: 6px;
                border: 1px solid var(--border-color);
            }
            
            .download-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .download-search {
                min-width: auto;
            }
            
            .download-pagination {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .download-pagination-info {
                min-width: auto;
                order: -1;
                width: 100%;
                text-align: center;
                margin-bottom: 5px;
            }
            
            /* 修复：手机端模态框样式 */
            .edit-modal-content {
                width: 95%;
                padding: 20px;
            }
            
            .sources-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .vod-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
            }
            
            .episode-grid {
                grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            }
            
            .download-list {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
            
            .detail-title {
                font-size: 20px;
            }
            
            /* 修复：小屏幕数据源选择 */
            .source-assignment {
                padding: 10px;
            }
            
            .sources-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* 动画效果 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        /* 通知样式 */
        .notice-bar {
            background: linear-gradient(90deg, rgba(255, 107, 107, 0.1), rgba(255, 158, 107, 0.1));
            color: #ff6b6b;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: var(--radius);
            border-left: 4px solid #ff6b6b;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .notice-bar i {
            margin-right: 8px;
        }
        
        /* 成功消息 */
        .success-message {
            background: linear-gradient(90deg, rgba(46, 204, 113, 0.1), rgba(39, 174, 96, 0.1));
            color: #2ecc71;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: var(--radius);
            border-left: 4px solid #2ecc71;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .success-message i {
            margin-right: 8px;
        }
        
        /* 编辑模态框 */
        .edit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        
        .edit-modal-content {
            background: var(--card-color);
            border-radius: var(--radius);
            padding: 25px;
            width: 95%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .edit-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .edit-modal-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .edit-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .edit-modal-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .edit-modal-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .edit-modal-form label {
            font-weight: 500;
            font-size: 14px;
        }
        
        .edit-modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background: var(--text-light);
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        /* 解析器选择器 */
        .parser-selector {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
            flex-wrap: wrap;
            background: rgba(0, 0, 0, 0.03);
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .parser-selector label {
            font-weight: 500;
            color: var(--text-color);
            margin: 0;
        }
        
        .parser-selector select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--card-color);
            color: var(--text-color);
            font-size: 14px;
            min-width: 200px;
        }
        
        .parser-info {
            color: var(--text-light);
            font-size: 13px;
            margin-left: 10px;
        }
    
        /* API源类型标签样式 */
        .source-type-tag {
            background: #3498db;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        .source-type-standard {
            background: #2ecc71;
        }
        
        .source-type-tvbox {
            background: #e74c3c;
        }
        
        /* 解析器管理员标签 */
        .parser-admin-tag {
            background: #f39c12;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        /* 新增：去插播接口标签样式 */
        .ad-removal-tag {
            background: #2ecc71;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        /* 修复：手机端数据源选择容器滚动 */
        .modal-sources-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.02);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <img src="https://t.alcy.cc/moemp?text=<?php echo urlencode(SITE_NAME); ?>" alt="Logo">
                <div class="logo-text"><?php echo SITE_NAME; ?></div>
            </div>
            <div class="datetime">
                <div class="date" id="current-date"></div>
                <div class="time" id="current-time"></div>
            </div>
        </header>

        <!-- 搜索框 -->
        <div class="search-container">
            <form action="" method="GET">
                <input type="text" 
                       name="keyword" 
                       class="search-box" 
                       placeholder="搜索影片..." 
                       value="<?php echo htmlspecialchars($keyword); ?>">
            </form>
        </div>
        
        <!-- API源选择器 -->
        <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span style="font-size: 14px;">数据源:</span>
            <select id="api-source-select" class="form-input" style="min-width: 150px; font-size: 14px;">
                <?php 
                $available_sources = get_available_api_sources();
                foreach ($available_sources as $key => $source): 
                    $is_admin_only = isset($source['admin_only']) && $source['admin_only'];
                    $source_type = isset($source['type']) ? $source['type'] : 'standard';
                ?>
                    <option value="<?php echo $key; ?>" <?php echo $_SESSION['current_api_source'] == $key ? 'selected' : ''; ?>>
                        <?php echo $source['name']; ?>
                        <?php if ($is_admin_only): ?> (管理员)<?php endif; ?>
                        <?php if ($source_type === 'tvbox'): ?> [TVBox]<?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button id="api-source-change" class="form-btn" style="font-size: 14px;">切换</button>
            
            <?php if (!isset($_SESSION['admin_logged_in'])): ?>
                <a href="?admin=1" style="margin-left: auto; color: var(--primary-color); text-decoration: none; font-size: 14px;">
                    <i class="bi bi-gear"></i> 管理
                </a>
            <?php else: ?>
                <a href="?action=admin_logout" style="margin-left: auto; color: var(--primary-color); text-decoration: none; font-size: 14px;">
                    <i class="bi bi-box-arrow-right"></i> 退出管理
                </a>
            <?php endif; ?>
        </div>

        <!-- 快速导航 -->
        <div class="quick-nav">
            <a href="?" class="nav-btn <?php echo empty($type_id) && empty($keyword) && empty($vod_id) && !isset($show_history) && !$admin ? 'active' : ''; ?>">首页</a>
            <a href="?action=history" class="nav-btn <?php echo isset($show_history) ? 'active' : ''; ?>">观看历史</a>
            <?php foreach ($categories as $category): ?>
                <a href="?type_id=<?php echo $category['type_id']; ?>" class="nav-btn <?php echo $type_id == $category['type_id'] ? 'active' : ''; ?>"><?php echo $category['type_name']; ?></a>
            <?php endforeach; ?>
        </div>

        <!-- 公告 -->
        <div class="notice-bar">
            <i class="bi bi-info-circle"></i>
            <span><?php echo SITE_NOTICE; ?></span>
        </div>

        <!-- 主要内容区域 -->
        <main>
            <?php if (!empty($msg)): ?>
                <div class="success-message fade-in">
                    <i class="bi bi-check-circle"></i>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($admin && !isset($_SESSION['admin_logged_in'])): ?>
                <!-- 管理员登录 -->
                <div class="admin-panel fade-in">
                    <h2 class="admin-header"><i class="bi bi-shield-lock"></i> 管理员登录</h2>
                    <?php if (isset($login_error)): ?>
                        <div style="color: #e74c3c; margin-bottom: 15px; padding: 10px; background: rgba(231, 76, 60, 0.1); border-radius: 8px; font-size: 14px;">
                            <?php echo $login_error; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="admin_login">
                        <div style="max-width: 300px; margin: 0 auto;">
                            <input type="password" name="password" class="form-input" placeholder="请输入管理员密码" required style="width: 100%; margin-bottom: 15px;">
                            <button type="submit" class="form-btn" style="width: 100%;">登录</button>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($admin && isset($_SESSION['admin_logged_in'])): ?>
                <!-- 后台管理 -->
                <div class="admin-panel fade-in">
                    <h2 class="admin-header"><i class="bi bi-gear"></i> 后台管理</h2>
                    
                    <h3 style="margin-bottom: 15px; font-size: 18px;">添加API源</h3>
                    <form class="admin-form" method="POST">
                        <input type="hidden" name="action" value="add_api_source">
                        <input type="text" name="name" class="form-input" placeholder="API源名称" required>
                        <input type="url" name="url" class="form-input" placeholder="API源URL" required>
                        <select name="type" class="form-input" style="font-size: 14px;">
                            <option value="standard">标准格式</option>
                            <option value="tvbox">TVBox格式</option>
                        </select>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                            <input type="checkbox" id="admin_only" name="admin_only" value="1">
                            <label for="admin_only">仅管理员可见</label>
                        </div>
                        <button type="submit" class="form-btn">添加</button>
                    </form>
                    
                    <h3 style="margin-top: 25px; font-size: 18px;">可用API源</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 15px;">
                        <?php 
                        $all_sources = array_merge($api_sources, $custom_sources);
                        foreach ($all_sources as $key => $source): 
                            $is_admin_only = isset($source['admin_only']) && $source['admin_only'];
                            $is_custom = strpos($key, 'custom_') === 0;
                            $source_type = isset($source['type']) ? $source['type'] : 'standard';
                            $type_class = $source_type === 'tvbox' ? 'source-type-tvbox' : 'source-type-standard';
                            $type_text = $source_type === 'tvbox' ? 'TVBox' : '标准';
                        ?>
                            <div style="background: var(--card-color); border-radius: var(--radius); padding: 15px; box-shadow: var(--shadow);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <div style="font-weight: 600; font-size: 16px;">
                                        <?php echo $source['name']; ?>
                                        <span class="source-type-tag <?php echo $type_class; ?>"><?php echo $type_text; ?></span>
                                    </div>
                                    <?php if ($is_admin_only): ?><span style="background: var(--primary-color); color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px;">管理员</span><?php endif; ?>
                                </div>
                                <div style="color: var(--text-light); font-size: 13px; margin-bottom: 12px; word-break: break-all;"><?php echo $source['base_url']; ?></div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="?api_source=<?php echo $key; ?>" class="form-btn" style="padding: 6px 12px; font-size: 13px; text-decoration: none;">
                                        <?php echo $_SESSION['current_api_source'] == $key ? '当前使用' : '使用此源'; ?>
                                    </a>
                                    <?php if ($is_custom): ?>
                                        <button class="form-btn edit-api-source" style="padding: 6px 12px; font-size: 13px; background: #f39c12; text-decoration: none;" data-key="<?php echo $key; ?>" data-name="<?php echo htmlspecialchars($source['name']); ?>" data-url="<?php echo htmlspecialchars($source['base_url']); ?>" data-admin-only="<?php echo $is_admin_only ? '1' : '0'; ?>" data-type="<?php echo $source_type; ?>">编辑</button>
                                        <a href="?admin=1&action=delete_api_source&key=<?php echo $key; ?>" class="form-btn" style="padding: 6px 12px; font-size: 13px; background: #e74c3c; text-decoration: none;" onclick="return confirm('确定要删除这个API源吗？')">删除</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 解析器管理 -->
                    <div class="parser-form-container">
                        <h3 style="margin-top: 25px; font-size: 18px;">添加解析器</h3>
                        <form class="parser-form" method="POST" id="parser-form">
                            <input type="hidden" name="action" value="add_parser">
                            <input type="text" name="name" class="form-input" placeholder="解析器名称" required>
                            <input type="url" name="url" class="form-input" placeholder="解析器URL" required>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <input type="checkbox" id="enabled" name="enabled" value="1" checked>
                                <label for="enabled">启用</label>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <input type="checkbox" id="admin_only_parser" name="admin_only" value="1">
                                <label for="admin_only_parser">仅管理员可用</label>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <input type="checkbox" id="for_all_sources" name="for_all_sources" value="1" checked>
                                <label for="for_all_sources">适用于所有数据源</label>
                            </div>
                            <button type="submit" class="form-btn">添加</button>
                        </form>
                        
                        <div class="source-assignment" id="source-assignment">
                            <label>选择适用的数据源:</label>
                            <div class="modal-sources-container">
                                <div class="sources-grid">
                                    <?php 
                                    $all_sources = array_merge($api_sources, $custom_sources);
                                    foreach ($all_sources as $key => $source): 
                                    ?>
                                        <div class="source-checkbox">
                                            <input type="checkbox" id="source_<?php echo $key; ?>" name="assigned_sources[]" value="<?php echo $key; ?>">
                                            <label for="source_<?php echo $key; ?>"><?php echo $source['name']; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h3 style="margin-top: 25px; font-size: 18px;">解析器列表</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 15px;">
                        <?php 
                        $parser_config = init_parser_config();
                        foreach ($parser_config as $key => $parser): 
                            $is_enabled = $parser['enabled'];
                            $is_admin_only = isset($parser['admin_only']) && $parser['admin_only'];
                        ?>
                            <div style="background: var(--card-color); border-radius: var(--radius); padding: 15px; box-shadow: var(--shadow);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <div style="font-weight: 600; font-size: 16px;">
                                        <?php echo $parser['name']; ?>
                                        <?php if ($is_admin_only): ?><span class="parser-admin-tag">管理员</span><?php endif; ?>
                                    </div>
                                    <span style="background: <?php echo $is_enabled ? '#2ecc71' : '#95a5a6'; ?>; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px;">
                                        <?php echo $is_enabled ? '已启用' : '已禁用'; ?>
                                    </span>
                                </div>
                                <div style="color: var(--text-light); font-size: 13px; margin-bottom: 12px; word-break: break-all;"><?php echo $parser['url']; ?></div>
                                <div style="color: var(--text-light); font-size: 12px; margin-bottom: 10px;">
                                    <?php 
                                    if ($parser['for_all_sources']) {
                                        echo '适用于所有数据源';
                                    } else {
                                        echo '适用于指定数据源: ';
                                        if (isset($parser['assigned_sources']) && is_array($parser['assigned_sources']) && !empty($parser['assigned_sources'])) {
                                            $assigned_names = [];
                                            foreach ($parser['assigned_sources'] as $source_key) {
                                                if (isset($all_sources[$source_key])) {
                                                    $assigned_names[] = $all_sources[$source_key]['name'];
                                                }
                                            }
                                            echo implode(', ', $assigned_names);
                                        } else {
                                            echo '无';
                                        }
                                    }
                                    ?>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="?admin=1&action=toggle_parser&key=<?php echo $key; ?>" class="form-btn" style="padding: 6px 12px; font-size: 13px; text-decoration: none;">
                                        <?php echo $is_enabled ? '禁用' : '启用'; ?>
                                    </a>
                                    <button class="form-btn edit-parser" style="padding: 6px 12px; font-size: 13px; background: #f39c12; text-decoration: none;" data-key="<?php echo $key; ?>" data-name="<?php echo htmlspecialchars($parser['name']); ?>" data-url="<?php echo htmlspecialchars($parser['url']); ?>" data-enabled="<?php echo $is_enabled ? '1' : '0'; ?>" data-for-all-sources="<?php echo $parser['for_all_sources'] ? '1' : '0'; ?>" data-admin-only="<?php echo $is_admin_only ? '1' : '0'; ?>" data-assigned-sources="<?php echo isset($parser['assigned_sources']) ? htmlspecialchars(json_encode($parser['assigned_sources'])) : '[]'; ?>">编辑</button>
                                    <a href="?admin=1&action=delete_parser&key=<?php echo $key; ?>" class="form-btn" style="padding: 6px 12px; font-size: 13px; background: #e74c3c; text-decoration: none;" onclick="return confirm('确定要删除这个解析器吗？')">删除</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 新增：去插播接口管理 -->
                    <div class="parser-form-container">
                        <h3 style="margin-top: 25px; font-size: 18px;">添加去插播接口</h3>
                        <form class="parser-form" method="POST" id="ad-removal-form">
                            <input type="hidden" name="action" value="add_ad_removal">
                            <input type="text" name="name" class="form-input" placeholder="去插播接口名称" required>
                            <input type="url" name="url" class="form-input" placeholder="去插播接口URL" required value="https://jx.dmcdn.top/?url=">
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <input type="checkbox" id="ad_enabled" name="enabled" value="1" checked>
                                <label for="ad_enabled">启用</label>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <input type="checkbox" id="ad_admin_only" name="admin_only" value="1">
                                <label for="ad_admin_only">仅管理员可用</label>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                                <input type="checkbox" id="ad_for_all_sources" name="for_all_sources" value="1" checked>
                                <label for="ad_for_all_sources">适用于所有数据源</label>
                            </div>
                            <button type="submit" class="form-btn">添加</button>
                        </form>
                        
                        <div class="source-assignment" id="ad-source-assignment">
                            <label>选择适用的数据源:</label>
                            <div class="modal-sources-container">
                                <div class="sources-grid">
                                    <?php 
                                    $all_sources = array_merge($api_sources, $custom_sources);
                                    foreach ($all_sources as $key => $source): 
                                    ?>
                                        <div class="source-checkbox">
                                            <input type="checkbox" id="ad_source_<?php echo $key; ?>" name="assigned_sources[]" value="<?php echo $key; ?>">
                                            <label for="ad_source_<?php echo $key; ?>"><?php echo $source['name']; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h3 style="margin-top: 25px; font-size: 18px;">去插播接口列表</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 15px;">
                        <?php 
                        $ad_removal_config = init_ad_removal_config();
                        foreach ($ad_removal_config as $key => $ad_removal): 
                            $is_enabled = $ad_removal['enabled'];
                            $is_admin_only = isset($ad_removal['admin_only']) && $ad_removal['admin_only'];
                        ?>
                            <div style="background: var(--card-color); border-radius: var(--radius); padding: 15px; box-shadow: var(--shadow);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <div style="font-weight: 600; font-size: 16px;">
                                        <?php echo $ad_removal['name']; ?>
                                        <?php if ($is_admin_only): ?><span class="parser-admin-tag">管理员</span><?php endif; ?>
                                    </div>
                                    <span style="background: <?php echo $is_enabled ? '#2ecc71' : '#95a5a6'; ?>; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px;">
                                        <?php echo $is_enabled ? '已启用' : '已禁用'; ?>
                                    </span>
                                </div>
                                <div style="color: var(--text-light); font-size: 13px; margin-bottom: 12px; word-break: break-all;"><?php echo $ad_removal['url']; ?></div>
                                <div style="color: var(--text-light); font-size: 12px; margin-bottom: 10px;">
                                    <?php 
                                    if ($ad_removal['for_all_sources']) {
                                        echo '适用于所有数据源';
                                    } else {
                                        echo '适用于指定数据源: ';
                                        if (isset($ad_removal['assigned_sources']) && is_array($ad_removal['assigned_sources']) && !empty($ad_removal['assigned_sources'])) {
                                            $assigned_names = [];
                                            foreach ($ad_removal['assigned_sources'] as $source_key) {
                                                if (isset($all_sources[$source_key])) {
                                                    $assigned_names[] = $all_sources[$source_key]['name'];
                                                }
                                            }
                                            echo implode(', ', $assigned_names);
                                        } else {
                                            echo '无';
                                        }
                                    }
                                    ?>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="?admin=1&action=toggle_ad_removal&key=<?php echo $key; ?>" class="form-btn" style="padding: 6px 12px; font-size: 13px; text-decoration: none;">
                                        <?php echo $is_enabled ? '禁用' : '启用'; ?>
                                    </a>
                                    <button class="form-btn edit-ad-removal" style="padding: 6px 12px; font-size: 13px; background: #f39c12; text-decoration: none;" data-key="<?php echo $key; ?>" data-name="<?php echo htmlspecialchars($ad_removal['name']); ?>" data-url="<?php echo htmlspecialchars($ad_removal['url']); ?>" data-enabled="<?php echo $is_enabled ? '1' : '0'; ?>" data-for-all-sources="<?php echo $ad_removal['for_all_sources'] ? '1' : '0'; ?>" data-admin-only="<?php echo $is_admin_only ? '1' : '0'; ?>" data-assigned-sources="<?php echo isset($ad_removal['assigned_sources']) ? htmlspecialchars(json_encode($ad_removal['assigned_sources'])) : '[]'; ?>">编辑</button>
                                    <a href="?admin=1&action=delete_ad_removal&key=<?php echo $key; ?>" class="form-btn" style="padding: 6px 12px; font-size: 13px; background: #e74c3c; text-decoration: none;" onclick="return confirm('确定要删除这个去插播接口吗？')">删除</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                        <h3 style="font-size: 18px;">系统信息</h3>
                        <div style="color: var(--text-light); display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; margin-top: 10px; font-size: 14px;">
                            <div>PHP版本: <?php echo phpversion(); ?></div>
                            <div>当前API源: <?php echo isset($all_sources[$_SESSION['current_api_source']]) ? $all_sources[$_SESSION['current_api_source']]['name'] : '未知'; ?></div>
                            <div>历史记录数量: <?php echo count($_SESSION['watch_history']); ?></div>
                            <div>自定义API源数量: <?php echo count($custom_sources); ?></div>
                            <div>解析器数量: <?php echo count($parser_config); ?></div>
                            <div>去插播接口数量: <?php echo count($ad_removal_config); ?></div>
                        </div>
                        
                        <!-- 新增：重载配置按钮 -->
                        <div style="margin-top: 15px; padding: 15px; background: rgba(52, 152, 219, 0.1); border-radius: 8px; border: 1px solid rgba(52, 152, 219, 0.3);">
                            <h4 style="margin-bottom: 10px; color: #3498db; font-size: 16px;">
                                <i class="bi bi-arrow-clockwise"></i> 配置重载工具
                            </h4>
                            <p style="font-size: 14px; color: var(--text-light); margin-bottom: 10px;">
                                如果您直接上传了 <code>custom_sources.json</code>、<code>parser_config.json</code> 或 <code>ad_removal_config.json</code> 文件，
                                请点击下方按钮重新加载配置。
                            </p>
                            <a href="?admin=1&reload_config=1" class="form-btn" style="background: #3498db; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="bi bi-arrow-clockwise"></i> 重新加载配置文件
                            </a>
                        </div>
                    </div>
                </div>
                
            <?php elseif (isset($show_history) && $show_history): ?>
                <!-- 历史记录页面 -->
                <div class="admin-panel fade-in">
                    <h2 class="admin-header"><i class="bi bi-clock-history"></i> 观看历史</h2>
                    <a href="?action=clear_history" class="form-btn" style="margin-bottom: 15px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;" onclick="return confirm('确定要清空所有观看历史吗？')">
                        <i class="bi bi-trash"></i> 清空历史记录
                    </a>
                    
                    <?php if (empty($_SESSION['watch_history'])): ?>
                        <div style="text-align: center; padding: 40px 0; color: var(--text-light);">
                            <i class="bi bi-inbox" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <div>暂无观看历史</div>
                        </div>
                    <?php else: ?>
                        <div class="vod-grid">
                            <?php foreach ($_SESSION['watch_history'] as $record): ?>
                                <a href="?vod_id=<?php echo $record['id']; ?>" class="vod-card fade-in">
                                    <div class="vod-poster">
                                        <?php
                                        $coverImage = !empty($record['pic']) ? $record['pic'] : DEFAULT_IMAGE . urlencode($record['name']);
                                        ?>
                                        <img src="<?php echo $coverImage; ?>" alt="<?php echo $record['name']; ?>" onerror="this.src='<?php echo DEFAULT_IMAGE . urlencode($record['name']); ?>'">
                                    </div>
                                    <div class="vod-info">
                                        <div class="vod-title"><?php echo $record['name']; ?></div>
                                        <div class="vod-meta">
                                            <span><?php echo $record['episode']; ?></span>
                                            <span><?php echo date('m-d', $record['timestamp']); ?></span>
                                        </div>
                                        <div style="color: var(--text-light); font-size: 11px; margin-top: 5px;">
                                            <?php 
                                            // 显示数据源信息
                                            if (isset($record['source_name'])) {
                                                echo '来源: ' . $record['source_name'];
                                            } elseif (isset($record['source']) && isset($all_sources[$record['source']])) {
                                                echo '来源: ' . $all_sources[$record['source']]['name'];
                                            } else {
                                                echo '来源: 未知';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($vod_id > 0 && isset($vod_detail)): ?>
                <!-- 影片详情页 -->
                <div class="detail-container fade-in">
                    <!-- 解析器选择器 -->
                    <?php if (count($available_parsers) > 0): ?>
                    <div class="parser-selector fade-in">
                        <label for="parser-select">选择解析器:</label>
                        <select id="parser-select">
                            <option value="">不使用解析器 (原始链接)</option>
                            <?php foreach ($available_parsers as $key => $parser): 
                                $is_admin_only = isset($parser['admin_only']) && $parser['admin_only'];
                            ?>
                                <option value="<?php echo $key; ?>" data-url="<?php echo htmlspecialchars($parser['url']); ?>">
                                    <?php echo $parser['name']; ?>
                                    <?php if ($is_admin_only): ?> (管理员)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="parser-info">解析器可以帮助解决视频无法播放的问题</span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 新增：去插播接口选择器 -->
                    <?php if (count($available_ad_removals) > 0): ?>
                    <div class="ad-removal-selector fade-in">
                        <label for="ad-removal-select">选择去插播接口:</label>
                        <select id="ad-removal-select">
                            <option value="">不使用去插播 (原始链接)</option>
                            <?php foreach ($available_ad_removals as $key => $ad_removal): 
                                $is_admin_only = isset($ad_removal['admin_only']) && $ad_removal['admin_only'];
                            ?>
                                <option value="<?php echo $key; ?>" data-url="<?php echo htmlspecialchars($ad_removal['url']); ?>">
                                    <?php echo $ad_removal['name']; ?>
                                    <?php if ($is_admin_only): ?> (管理员)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="ad-removal-info">去插播接口可以去除.m3u8视频中的广告</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-header">
                        <div class="detail-poster">
                            <?php
                            $coverImage = !empty($vod_detail['vod_pic']) ? $vod_detail['vod_pic'] : DEFAULT_IMAGE . urlencode($vod_detail['vod_name']);
                            ?>
                            <img src="<?php echo $coverImage; ?>" alt="<?php echo $vod_detail['vod_name']; ?>" onerror="this.src='<?php echo DEFAULT_IMAGE . urlencode($vod_detail['vod_name']); ?>'">
                        </div>
                        <div class="detail-content">
                            <h1 class="detail-title"><?php echo $vod_detail['vod_name']; ?></h1>
                            <div class="detail-subtitle"><?php echo $vod_detail['vod_sub']; ?></div>
                            
                            <div class="detail-meta">
                                <div class="meta-item">
                                    <i class="bi bi-tag"></i>
                                    <span>类型: <?php echo $vod_detail['type_name']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-globe"></i>
                                    <span>地区: <?php echo $vod_detail['vod_area']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-calendar"></i>
                                    <span>年份: <?php echo $vod_detail['vod_year']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-clock"></i>
                                    <span>更新: <?php echo $vod_detail['vod_remarks']; ?></span>
                                </div>
                            </div>
                            
                            <div class="detail-meta">
                                <div class="meta-item">
                                    <i class="bi bi-person"></i>
                                    <span>导演: <?php echo $vod_detail['vod_director']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-people"></i>
                                    <span>演员: <?php echo $vod_detail['vod_actor']; ?></span>
                                </div>
                            </div>
                            
                            <div class="detail-description">
                                <?php echo $vod_detail['vod_blurb']; ?>
                            </div>
                            
                            <div class="action-buttons">
                                <button class="action-btn btn-primary" id="play-first">
                                    <i class="bi bi-play-circle"></i> 播放第一集
                                </button>
                                <button class="action-btn btn-primary" id="download-all">
                                    <i class="bi bi-download"></i> 下载第一集
                                </button>
                                <button class="action-btn btn-primary" id="share-detail">
                                    <i class="bi bi-share"></i> 分享影片
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($vod_detail['vod_play_from']) && !empty($vod_detail['vod_play_url'])): ?>
                        <?php $play_list = parse_play_list($vod_detail['vod_play_from'], $vod_detail['vod_play_url']); ?>
                        <?php foreach ($play_list as $source_index => $source): ?>
                            <div class="episode-section">
                                <h3 class="section-title"><i class="bi bi-play-btn"></i> 播放来源: <?php echo $source['source']; ?></h3>
                                
                                <!-- 剧集搜索和分页控件 -->
                                <div class="episode-controls">
                                    <div class="episode-search">
                                        <input type="text" class="episode-search-input" data-source-index="<?php echo $source_index; ?>" placeholder="搜索剧集...">
                                        <button class="episode-search-clear" data-source-index="<?php echo $source_index; ?>">×</button>
                                    </div>
                                    <div class="episode-pagination">
                                        <button class="episode-pagination-btn" data-action="prev" data-source-index="<?php echo $source_index; ?>">上一页</button>
                                        <span class="episode-pagination-info" data-source-index="<?php echo $source_index; ?>">1/1</span>
                                        <button class="episode-pagination-btn" data-action="next" data-source-index="<?php echo $source_index; ?>">下一页</button>
                                        <select class="episode-group-selector" data-source-index="<?php echo $source_index; ?>">
                                            <option value="20">每页20集</option>
                                            <option value="40">每页40集</option>
                                            <option value="60">每页60集</option>
                                            <option value="0">显示全部</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="episode-grid" data-source-index="<?php echo $source_index; ?>">
                                    <?php foreach ($source['episodes'] as $episode_index => $episode): ?>
                                        <div class="episode-btn play-episode" 
                                             data-url="<?php echo htmlspecialchars($episode['url']); ?>" 
                                             data-vod-id="<?php echo $vod_id; ?>" 
                                             data-vod-name="<?php echo htmlspecialchars($vod_detail['vod_name']); ?>" 
                                             data-episode-name="<?php echo htmlspecialchars($episode['name']); ?>"
                                             data-vod-pic="<?php echo !empty($vod_detail['vod_pic']) ? $vod_detail['vod_pic'] : ''; ?>"
                                             data-source-index="<?php echo $source_index; ?>"
                                             data-episode-index="<?php echo $episode_index; ?>">
                                            <?php echo $episode['name']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- 修复：下载资源部分 -->
                    <?php if (!empty($down_list)): ?>
                        <div class="download-section">
                            <h3 class="section-title"><i class="bi bi-download"></i> 下载资源</h3>
                            <p style="color: var(--text-light); font-size: 14px; margin-bottom: 15px;">点击下方链接下载对应剧集</p>
                            <?php foreach ($down_list as $source_index => $source): ?>
                                <?php if (!empty($source['episodes'])): ?>
                                    <h4 style="font-size: 16px; margin: 15px 0 10px 0; color: var(--text-color);">来源: <?php echo $source['source']; ?></h4>
                                    
                                    <!-- 下载资源搜索和分页控件 -->
                                    <div class="download-controls" id="download-controls-<?php echo $source_index; ?>">
                                        <div class="download-search">
                                            <input type="text" class="download-search-input" data-source-index="<?php echo $source_index; ?>" placeholder="在 <?php echo $source['source']; ?> 中搜索下载资源...">
                                            <button class="download-search-clear" data-source-index="<?php echo $source_index; ?>">×</button>
                                        </div>
                                        <div class="download-pagination">
                                            <button class="download-pagination-btn" data-action="prev" data-source-index="<?php echo $source_index; ?>">上一页</button>
                                            <span class="download-pagination-info" data-source-index="<?php echo $source_index; ?>">1/1</span>
                                            <button class="download-pagination-btn" data-action="next" data-source-index="<?php echo $source_index; ?>">下一页</button>
                                            <select class="download-group-selector" data-source-index="<?php echo $source_index; ?>">
                                                <option value="10">每页10个</option>
                                                <option value="20">每页20个</option>
                                                <option value="50">每页50个</option>
                                                <option value="0">显示全部</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="download-list" id="download-list-<?php echo $source_index; ?>">
                                        <?php foreach ($source['episodes'] as $episode_index => $episode): ?>
                                            <a href="<?php echo $episode['url']; ?>" class="episode-download" download data-source-index="<?php echo $source_index; ?>" data-episode-index="<?php echo $episode_index; ?>">
                                                <i class="bi bi-download"></i> <?php echo $episode['name']; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <!-- 列表页/搜索结果页 -->
                <?php if (!empty($keyword)): ?>
                    <div style="background: var(--card-color); border-radius: var(--radius); padding: 15px; margin-bottom: 15px; box-shadow: var(--shadow);">
                        <h3 style="margin-bottom: 5px;">搜索关键词: "<span style="color: var(--primary-color);"><?php echo htmlspecialchars($keyword); ?></span>"</h3>
                        <p style="color: var(--text-light); font-size: 14px;">找到 <?php echo $total; ?> 条结果</p>
                    </div>
                <?php endif; ?>
                
                <div class="vod-grid">
                    <?php foreach ($vod_list as $vod): ?>
                        <a href="?vod_id=<?php echo $vod['vod_id']; ?>" class="vod-card fade-in">
                            <div class="vod-poster">
                                <?php
                                $vodImage = !empty($vod['vod_pic']) ? $vod['vod_pic'] : DEFAULT_IMAGE . urlencode($vod['vod_name']);
                                ?>
                                <img src="<?php echo $vodImage; ?>" alt="<?php echo $vod['vod_name']; ?>" onerror="this.src='<?php echo DEFAULT_IMAGE . urlencode($vod['vod_name']); ?>'">
                            </div>
                            <div class="vod-info">
                                <div class="vod-title"><?php echo $vod['vod_name']; ?></div>
                                <div class="vod-meta">
                                    <span><?php echo $vod['vod_remarks']; ?></span>
                                    <span><?php echo $vod['type_name']; ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($pagecount > 1): ?>
                    <div class="pagination fade-in">
                        <?php if ($page > 1): ?>
                            <a href="?<?php 
                                echo !empty($keyword) ? 'keyword='.urlencode($keyword).'&' : ''; 
                                echo $type_id > 0 ? 'type_id='.$type_id.'&' : ''; 
                                echo 'page='.($page-1); 
                            ?>" class="page-link"><i class="bi bi-chevron-left"></i> 上一页</a>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 3);
                        $end = min($pagecount, $start + 6);
                        
                        if ($end - $start < 6) {
                            $start = max(1, $end - 6);
                        }
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?<?php 
                                echo !empty($keyword) ? 'keyword='.urlencode($keyword).'&' : ''; 
                                echo $type_id > 0 ? 'type_id='.$type_id.'&' : ''; 
                                echo 'page='.$i; 
                            ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $pagecount): ?>
                            <a href="?<?php 
                                echo !empty($keyword) ? 'keyword='.urlencode($keyword).'&' : ''; 
                                echo $type_id > 0 ? 'type_id='.$type_id.'&' : ''; 
                                echo 'page='.($page+1); 
                            ?>" class="page-link">下一页 <i class="bi bi-chevron-right"></i></a>
                        <?php endif; ?>
                        
                        <?php if ($pagecount > 10): ?>
                            <div class="page-jump">
                                <input type="number" id="page-jump-input" class="page-jump-input" min="1" max="<?php echo $pagecount; ?>" value="<?php echo $page; ?>">
                                <button id="page-jump-btn" class="page-jump-btn">跳转</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
        
        <footer style="text-align: center; padding: 30px 0; margin-top: 30px; color: var(--text-light); font-size: 14px; border-top: 1px solid var(--border-color);">
            <div>Copyright © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</div>
            <div style="margin-top: 5px;">本站所有视频均来自网络，仅供学习交流使用，请于下载后24小时内删除</div>
        </footer>
    </div>

    <!-- 一键下滑按钮 -->
    <div class="scroll-down-btn" id="scroll-down-btn">
        <i class="bi bi-chevron-down"></i>
    </div>

    <!-- 返回顶部按钮 -->
    <div class="scroll-to-top" id="scroll-to-top">
        <i class="bi bi-chevron-up"></i>
    </div>

    <!-- 播放器模态框 -->
    <div id="player-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 1000; justify-content: center; align-items: center;">
        <div style="width: 95%; max-width: 1000px; background: #1a1a1a; border-radius: 10px; overflow: hidden; position: relative;">
            <button id="close-player" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 50%; width: 35px; height: 35px; cursor: pointer; z-index: 1001; font-size: 18px;">×</button>
            
            <!-- 视频错误提示 -->
            <div class="video-error" id="video-error" style="display: none; text-align: center; padding: 25px; color: #ff6b6b; background: rgba(255, 107, 107, 0.15); border-radius: 10px; margin: 20px; border-left: 4px solid #ff6b6b; animation: fadeIn 0.5s ease;">
                <h3 style="margin-bottom: 15px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <i class="bi bi-exclamation-circle"></i> 视频播放遇到问题
                </h3>
                <p>如果视频无法正常播放，请尝试以下解决方案：</p>
                <div class="alternative-players" id="alternative-players" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; justify-content: center;">
                    <button class="alt-player-btn" id="try-external" style="padding: 10px 18px; background: rgba(52, 152, 219, 0.25); border: 1px solid rgba(52, 152, 219, 0.35); border-radius: 8px; color: #3498db; cursor: pointer; transition: all 0.3s ease;">尝试外部播放器</button>
                    <button class="alt-player-btn" id="try-download" style="padding: 10px 18px; background: rgba(52, 152, 219, 0.25); border: 1px solid rgba(52, 152, 219, 0.35); border-radius: 8px; color: #3498db; cursor: pointer; transition: all 0.3s ease;">尝试下载后观看</button>
                    <button class="alt-player-btn" id="refresh-video" style="padding: 10px 18px; background: rgba(52, 152, 219, 0.25); border: 1px solid rgba(52, 152, 219, 0.35); border-radius: 8px; color: #3498db; cursor: pointer; transition: all 0.3s ease;">刷新视频</button>
                </div>
            </div>
            
            <div id="video-container" style="width: 100%; padding-bottom: 56.25%; position: relative;">
                <iframe id="video-player" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" allowfullscreen></iframe>
            </div>
            <div class="player-controls" style="display: flex; justify-content: space-between; padding: 15px; background: rgba(0, 0, 0, 0.75); color: white;">
                <div class="episode-controls" style="display: flex; gap: 10px;">
                    <button class="episode-control-btn" id="prev-episode" disabled style="padding: 8px 15px; background: rgba(255, 255, 255, 0.12); color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px;">
                        <i class="bi bi-chevron-left"></i> 上一集
                    </button>
                    <button class="episode-control-btn" id="next-episode" disabled style="padding: 8px 15px; background: rgba(255, 255, 255, 0.12); color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px;">
                        下一集 <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <div class="share-controls" style="display: flex; gap: 10px;">
                    <button class="share-btn" id="share-episode" style="padding: 8px 15px; background: rgba(255, 255, 255, 0.12); color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px;">
                        <i class="bi bi-share-alt"></i> 分享
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 分享模态框 -->
    <div class="share-modal" id="share-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); z-index: 1002; justify-content: center; align-items: center;">
        <div class="share-content" style="background: linear-gradient(135deg, var(--card-color) 0%, var(--card-color) 100%); border-radius: 15px; padding: 25px; width: 92%; max-width: 500px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);">
            <div class="share-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                <h3 style="color: var(--text-color); font-size: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-share-alt"></i> 分享
                </h3>
                <button class="close-player" id="close-share" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-light);">×</button>
            </div>
            <div class="share-options" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                <div class="share-option" data-share="weibo" style="display: flex; flex-direction: column; align-items: center; padding: 15px 10px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; cursor: pointer; transition: all 0.3s ease; border: 1px solid var(--border-color);">
                    <i class="bi bi-weibo" style="color: #e6162d; font-size: 28px; margin-bottom: 8px;"></i>
                    <span style="font-size: 14px;">微博</span>
                </div>
                <div class="share-option" data-share="wechat" style="display: flex; flex-direction: column; align-items: center; padding: 15px 10px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; cursor: pointer; transition: all 0.3s ease; border: 1px solid var(--border-color);">
                    <i class="bi bi-wechat" style="color: #07c160; font-size: 28px; margin-bottom: 8px;"></i>
                    <span style="font-size: 14px;">微信</span>
                </div>
                <div class="share-option" data-share="qq" style="display: flex; flex-direction: column; align-items: center; padding: 15px 10px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; cursor: pointer; transition: all 0.3s ease; border: 1px solid var(--border-color);">
                    <i class="bi bi-qq" style="color: #12b7f5; font-size: 28px; margin-bottom: 8px;"></i>
                    <span style="font-size: 14px;">QQ</span>
                </div>
                <div class="share-option" data-share="qzone" style="display: flex; flex-direction: column; align-items: center; padding: 15px 10px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; cursor: pointer; transition: all 0.3s ease; border: 1px solid var(--border-color);">
                    <i class="bi bi-star" style="color: #f7ce3b; font-size: 28px; margin-bottom: 8px;"></i>
                    <span style="font-size: 14px;">QQ空间</span>
                </div>
                <div class="share-option" data-share="douban" style="display: flex; flex-direction: column; align-items: center; padding: 15px 10px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; cursor: pointer; transition: all 0.3s ease; border: 1px solid var(--border-color);">
                    <i class="bi bi-film" style="color: #3ca353; font-size: 28px; margin-bottom: 8px;"></i>
                    <span style="font-size: 14px;">豆瓣</span>
                </div>
                <div class="share-option" data-share="link" style="display: flex; flex-direction: column; align-items: center; padding: 15px 10px; background: rgba(0, 0, 0, 0.05); border-radius: 10px; cursor: pointer; transition: all 0.3s ease; border: 1px solid var(--border-color);">
                    <i class="bi bi-link" style="color: var(--primary-color); font-size: 28px; margin-bottom: 8px;"></i>
                    <span style="font-size: 14px;">复制链接</span>
                </div>
            </div>
            <div class="share-link" style="display: flex; margin-top: 20px;">
                <input type="text" id="share-url" readonly style="flex: 1; padding: 12px; background: rgba(0, 0, 0, 0.05); border: 1px solid var(--border-color); border-radius: 8px 0 0 8px; color: var(--text-color); font-size: 14px;">
                <button id="copy-link" style="padding: 12px 18px; background: var(--primary-color); color: white; border: none; border-radius: 0 8px 8px 0; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">
                    <i class="bi bi-copy"></i> 复制
                </button>
            </div>
        </div>
    </div>

    <!-- API源编辑模态框 -->
    <div class="edit-modal" id="api-source-edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3 class="edit-modal-title">编辑API源</h3>
                <button class="edit-modal-close" id="close-api-edit">×</button>
            </div>
            <form class="edit-modal-form" id="api-source-edit-form" method="POST">
                <input type="hidden" name="action" value="edit_api_source">
                <input type="hidden" name="key" id="edit-api-key">
                <div class="form-group">
                    <label for="edit-api-name">API源名称</label>
                    <input type="text" id="edit-api-name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit-api-url">API源URL</label>
                    <input type="url" id="edit-api-url" name="url" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit-api-type">API源类型</label>
                    <select id="edit-api-type" name="type" class="form-input">
                        <option value="standard">标准格式</option>
                        <option value="tvbox">TVBox格式</option>
                    </select>
                </div>
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="edit-api-admin-only" name="admin_only" value="1">
                        <label for="edit-api-admin-only">仅管理员可见</label>
                    </div>
                </div>
                <div class="edit-modal-actions">
                    <button type="submit" class="form-btn">保存</button>
                    <button type="button" class="form-btn btn-secondary" id="cancel-api-edit">取消</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 解析器编辑模态框 -->
    <div class="edit-modal" id="parser-edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3 class="edit-modal-title">编辑解析器</h3>
                <button class="edit-modal-close" id="close-parser-edit">×</button>
            </div>
            <form class="edit-modal-form" id="parser-edit-form" method="POST">
                <input type="hidden" name="action" value="edit_parser">
                <input type="hidden" name="key" id="edit-parser-key">
                <div class="form-group">
                    <label for="edit-parser-name">解析器名称</label>
                    <input type="text" id="edit-parser-name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit-parser-url">解析器URL</label>
                    <input type="url" id="edit-parser-url" name="url" class="form-input" required>
                </div>
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="edit-parser-enabled" name="enabled" value="1">
                        <label for="edit-parser-enabled">启用</label>
                    </div>
                </div>
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="edit-parser-admin-only" name="admin_only" value="1">
                        <label for="edit-parser-admin-only">仅管理员可用</label>
                    </div>
                </div>
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="edit-parser-for-all-sources" name="for_all_sources" value="1">
                        <label for="edit-parser-for-all-sources">适用于所有数据源</label>
                    </div>
                </div>
                <div class="form-group" id="assigned-sources-container" style="display: none;">
                    <label>选择适用的数据源:</label>
                    <div class="modal-sources-container">
                        <div class="sources-grid" style="display: grid; grid-template-columns: 1fr; gap: 8px; margin-top: 8px;">
                            <?php 
                            $all_sources = array_merge($api_sources, $custom_sources);
                            foreach ($all_sources as $key => $source): 
                            ?>
                                <div class="source-checkbox">
                                    <input type="checkbox" id="edit-source-<?php echo $key; ?>" name="assigned_sources[]" value="<?php echo $key; ?>">
                                    <label for="edit-source-<?php echo $key; ?>" style="font-size: 13px;"><?php echo $source['name']; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="edit-modal-actions">
                    <button type="submit" class="form-btn">保存</button>
                    <button type="button" class="form-btn btn-secondary" id="cancel-parser-edit">取消</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 新增：去插播接口编辑模态框 -->
    <div class="edit-modal" id="ad-removal-edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3 class="edit-modal-title">编辑去插播接口</h3>
                <button class="edit-modal-close" id="close-ad-removal-edit">×</button>
            </div>
            <form class="edit-modal-form" id="ad-removal-edit-form" method="POST">
                <input type="hidden" name="action" value="edit_ad_removal">
                <input type="hidden" name="key" id="edit-ad-removal-key">
                <div class="form-group">
                    <label for="edit-ad-removal-name">去插播接口名称</label>
                    <input type="text" id="edit-ad-removal-name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit-ad-removal-url">去插播接口URL</label>
                    <input type="url" id="edit-ad-removal-url" name="url" class="form-input" required>
                </div>
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="edit-ad-removal-enabled" name="enabled" value="1">
                        <label for="edit-ad-removal-enabled">启用</label>
                    </div>
                </div>
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="edit-ad-removal-admin-only" name="admin_only" value="1">
                        <label for="edit-ad-removal-admin-only">仅管理员可用</label>
                    </div>
                </div>
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="edit-ad-removal-for-all-sources" name="for_all_sources" value="1">
                        <label for="edit-ad-removal-for-all-sources">适用于所有数据源</label>
                    </div>
                </div>
                <div class="form-group" id="ad-removal-assigned-sources-container" style="display: none;">
                    <label>选择适用的数据源:</label>
                    <div class="modal-sources-container">
                        <div class="sources-grid" style="display: grid; grid-template-columns: 1fr; gap: 8px; margin-top: 8px;">
                            <?php 
                            $all_sources = array_merge($api_sources, $custom_sources);
                            foreach ($all_sources as $key => $source): 
                            ?>
                                <div class="source-checkbox">
                                    <input type="checkbox" id="edit-ad-removal-source-<?php echo $key; ?>" name="assigned_sources[]" value="<?php echo $key; ?>">
                                    <label for="edit-ad-removal-source-<?php echo $key; ?>" style="font-size: 13px;"><?php echo $source['name']; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="edit-modal-actions">
                    <button type="submit" class="form-btn">保存</button>
                    <button type="button" class="form-btn btn-secondary" id="cancel-ad-removal-edit">取消</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 更新日期和时间
        function updateDateTime() {
            const now = new Date();
            const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
            
            document.getElementById('current-date').textContent = now.toLocaleDateString('zh-CN', dateOptions);
            document.getElementById('current-time').textContent = now.toLocaleTimeString('zh-CN', timeOptions);
        }
        
        // 初始更新
        updateDateTime();
        // 每秒更新一次时间
        setInterval(updateDateTime, 1000);
        
        // API源切换
        document.getElementById('api-source-change').addEventListener('click', function() {
            const select = document.getElementById('api-source-select');
            const selectedValue = select.value;
            window.location.href = '?api_source=' + selectedValue;
        });
        
        // 返回顶部按钮
        const scrollToTopBtn = document.getElementById('scroll-to-top');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        });
        
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // 一键下滑按钮
        const scrollDownBtn = document.getElementById('scroll-down-btn');
        
        window.addEventListener('scroll', function() {
            // 如果页面内容高度大于视窗高度，并且当前不在页面底部，显示下滑按钮
            if (document.body.scrollHeight > window.innerHeight && 
                window.pageYOffset < document.body.scrollHeight - window.innerHeight - 100) {
                scrollDownBtn.classList.add('show');
            } else {
                scrollDownBtn.classList.remove('show');
            }
        });
        
        scrollDownBtn.addEventListener('click', function() {
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        });
        
        // 页面跳转
        const pageJumpBtn = document.getElementById('page-jump-btn');
        if (pageJumpBtn) {
            pageJumpBtn.addEventListener('click', function() {
                const pageInput = document.getElementById('page-jump-input');
                const pageNum = parseInt(pageInput.value);
                const maxPage = parseInt(pageInput.max);
                
                if (pageNum >= 1 && pageNum <= maxPage) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('page', pageNum);
                    window.location.href = currentUrl.toString();
                } else {
                    alert('请输入有效的页码 (1-' + maxPage + ')');
                }
            });
            
            document.getElementById('page-jump-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    pageJumpBtn.click();
                }
            });
        }
        
        // 播放第一集按钮
        const playFirstBtn = document.getElementById('play-first');
        if (playFirstBtn) {
            playFirstBtn.addEventListener('click', function() {
                const firstEpisode = document.querySelector('.play-episode');
                if (firstEpisode) {
                    firstEpisode.click();
                }
            });
        }
        
        // 下载第一集按钮
        const downloadAllBtn = document.getElementById('download-all');
        if (downloadAllBtn) {
            downloadAllBtn.addEventListener('click', function() {
                const firstDownload = document.querySelector('.episode-download');
                if (firstDownload) {
                    firstDownload.click();
                }
            });
        }
        
        // 分享影片按钮
        const shareDetailBtn = document.getElementById('share-detail');
        if (shareDetailBtn) {
            shareDetailBtn.addEventListener('click', function() {
                const shareUrl = window.location.href;
                const shareTitle = document.querySelector('.detail-title').textContent;
                openShareModal(shareUrl, shareTitle);
            });
        }
        
        // 解析器表单处理
        const forAllSourcesCheckbox = document.getElementById('for_all_sources');
        const sourceAssignmentDiv = document.getElementById('source-assignment');
        
        if (forAllSourcesCheckbox && sourceAssignmentDiv) {
            forAllSourcesCheckbox.addEventListener('change', function() {
                if (!this.checked) {
                    sourceAssignmentDiv.classList.add('show');
                } else {
                    sourceAssignmentDiv.classList.remove('show');
                }
            });
        }
        
        // 新增：去插播接口表单处理
        const adForAllSourcesCheckbox = document.getElementById('ad_for_all_sources');
        const adSourceAssignmentDiv = document.getElementById('ad-source-assignment');
        
        if (adForAllSourcesCheckbox && adSourceAssignmentDiv) {
            adForAllSourcesCheckbox.addEventListener('change', function() {
                if (!this.checked) {
                    adSourceAssignmentDiv.classList.add('show');
                } else {
                    adSourceAssignmentDiv.classList.remove('show');
                }
            });
        }
        
        // API源编辑
        const editApiSourceButtons = document.querySelectorAll('.edit-api-source');
        const apiSourceEditModal = document.getElementById('api-source-edit-modal');
        const closeApiEdit = document.getElementById('close-api-edit');
        const cancelApiEdit = document.getElementById('cancel-api-edit');
        
        editApiSourceButtons.forEach(button => {
            button.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                const name = this.getAttribute('data-name');
                const url = this.getAttribute('data-url');
                const adminOnly = this.getAttribute('data-admin-only');
                const type = this.getAttribute('data-type');
                
                document.getElementById('edit-api-key').value = key;
                document.getElementById('edit-api-name').value = name;
                document.getElementById('edit-api-url').value = url;
                document.getElementById('edit-api-type').value = type;
                document.getElementById('edit-api-admin-only').checked = adminOnly === '1';
                
                apiSourceEditModal.style.display = 'flex';
            });
        });
        
        closeApiEdit.addEventListener('click', function() {
            apiSourceEditModal.style.display = 'none';
        });
        
        cancelApiEdit.addEventListener('click', function() {
            apiSourceEditModal.style.display = 'none';
        });
        
        // 解析器编辑
        const editParserButtons = document.querySelectorAll('.edit-parser');
        const parserEditModal = document.getElementById('parser-edit-modal');
        const closeParserEdit = document.getElementById('close-parser-edit');
        const cancelParserEdit = document.getElementById('cancel-parser-edit');
        const editParserForAllSources = document.getElementById('edit-parser-for-all-sources');
        const assignedSourcesContainer = document.getElementById('assigned-sources-container');
        
        editParserButtons.forEach(button => {
            button.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                const name = this.getAttribute('data-name');
                const url = this.getAttribute('data-url');
                const enabled = this.getAttribute('data-enabled');
                const forAllSources = this.getAttribute('data-for-all-sources');
                const adminOnly = this.getAttribute('data-admin-only');
                const assignedSources = JSON.parse(this.getAttribute('data-assigned-sources'));
                
                document.getElementById('edit-parser-key').value = key;
                document.getElementById('edit-parser-name').value = name;
                document.getElementById('edit-parser-url').value = url;
                document.getElementById('edit-parser-enabled').checked = enabled === '1';
                document.getElementById('edit-parser-for-all-sources').checked = forAllSources === '1';
                document.getElementById('edit-parser-admin-only').checked = adminOnly === '1';
                
                // 处理分配的数据源
                if (forAllSources === '1') {
                    assignedSourcesContainer.style.display = 'none';
                } else {
                    assignedSourcesContainer.style.display = 'block';
                    
                    // 清除所有复选框
                    document.querySelectorAll('#assigned-sources-container input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // 设置已分配的数据源
                    assignedSources.forEach(sourceKey => {
                        const checkbox = document.getElementById('edit-source-' + sourceKey);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                }
                
                parserEditModal.style.display = 'flex';
            });
        });
        
        closeParserEdit.addEventListener('click', function() {
            parserEditModal.style.display = 'none';
        });
        
        cancelParserEdit.addEventListener('click', function() {
            parserEditModal.style.display = 'none';
        });
        
        // 监听"适用于所有数据源"复选框的变化
        if (editParserForAllSources) {
            editParserForAllSources.addEventListener('change', function() {
                if (!this.checked) {
                    assignedSourcesContainer.style.display = 'block';
                } else {
                    assignedSourcesContainer.style.display = 'none';
                }
            });
        }
        
        // 新增：去插播接口编辑
        const editAdRemovalButtons = document.querySelectorAll('.edit-ad-removal');
        const adRemovalEditModal = document.getElementById('ad-removal-edit-modal');
        const closeAdRemovalEdit = document.getElementById('close-ad-removal-edit');
        const cancelAdRemovalEdit = document.getElementById('cancel-ad-removal-edit');
        const editAdRemovalForAllSources = document.getElementById('edit-ad-removal-for-all-sources');
        const adRemovalAssignedSourcesContainer = document.getElementById('ad-removal-assigned-sources-container');
        
        editAdRemovalButtons.forEach(button => {
            button.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                const name = this.getAttribute('data-name');
                const url = this.getAttribute('data-url');
                const enabled = this.getAttribute('data-enabled');
                const forAllSources = this.getAttribute('data-for-all-sources');
                const adminOnly = this.getAttribute('data-admin-only');
                const assignedSources = JSON.parse(this.getAttribute('data-assigned-sources'));
                
                document.getElementById('edit-ad-removal-key').value = key;
                document.getElementById('edit-ad-removal-name').value = name;
                document.getElementById('edit-ad-removal-url').value = url;
                document.getElementById('edit-ad-removal-enabled').checked = enabled === '1';
                document.getElementById('edit-ad-removal-for-all-sources').checked = forAllSources === '1';
                document.getElementById('edit-ad-removal-admin-only').checked = adminOnly === '1';
                
                // 处理分配的数据源
                if (forAllSources === '1') {
                    adRemovalAssignedSourcesContainer.style.display = 'none';
                } else {
                    adRemovalAssignedSourcesContainer.style.display = 'block';
                    
                    // 清除所有复选框
                    document.querySelectorAll('#ad-removal-assigned-sources-container input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // 设置已分配的数据源
                    assignedSources.forEach(sourceKey => {
                        const checkbox = document.getElementById('edit-ad-removal-source-' + sourceKey);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                }
                
                adRemovalEditModal.style.display = 'flex';
            });
        });
        
        closeAdRemovalEdit.addEventListener('click', function() {
            adRemovalEditModal.style.display = 'none';
        });
        
        cancelAdRemovalEdit.addEventListener('click', function() {
            adRemovalEditModal.style.display = 'none';
        });
        
        // 监听"适用于所有数据源"复选框的变化
        if (editAdRemovalForAllSources) {
            editAdRemovalForAllSources.addEventListener('change', function() {
                if (!this.checked) {
                    adRemovalAssignedSourcesContainer.style.display = 'block';
                } else {
                    adRemovalAssignedSourcesContainer.style.display = 'none';
                }
            });
        }
        
        // 关闭模态框时点击外部区域
        window.addEventListener('click', function(event) {
            if (event.target === apiSourceEditModal) {
                apiSourceEditModal.style.display = 'none';
            }
            if (event.target === parserEditModal) {
                parserEditModal.style.display = 'none';
            }
            if (event.target === adRemovalEditModal) {
                adRemovalEditModal.style.display = 'none';
            }
        });

        // 修复：下载资源搜索和分页功能
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化下载资源搜索和分页
            initializeDownloadSearchAndPagination();
            
            // 初始化剧集搜索和分页
            initializeEpisodeSearchAndPagination();
        });
        
        // 修复：初始化下载资源搜索和分页功能
        function initializeDownloadSearchAndPagination() {
            // 处理下载资源搜索
            const downloadSearchInputs = document.querySelectorAll('.download-search-input');
            const downloadSearchClears = document.querySelectorAll('.download-search-clear');
            
            downloadSearchInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const sourceIndex = this.getAttribute('data-source-index');
                    const searchTerm = this.value.toLowerCase();
                    const clearBtn = document.querySelector('.download-search-clear[data-source-index="' + sourceIndex + '"]');
                    
                    // 显示/隐藏清除按钮
                    if (searchTerm.length > 0) {
                        clearBtn.style.display = 'block';
                    } else {
                        clearBtn.style.display = 'none';
                    }
                    
                    // 过滤下载资源
                    filterDownloadItems(sourceIndex, searchTerm);
                });
            });
            
            // 下载资源搜索清除按钮
            downloadSearchClears.forEach(btn => {
                btn.addEventListener('click', function() {
                    const sourceIndex = this.getAttribute('data-source-index');
                    const searchInput = document.querySelector('.download-search-input[data-source-index="' + sourceIndex + '"]');
                    searchInput.value = '';
                    this.style.display = 'none';
                    filterDownloadItems(sourceIndex, '');
                });
            });
            
            // 下载资源分页按钮
            const downloadPaginationBtns = document.querySelectorAll('.download-pagination-btn');
            downloadPaginationBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('data-action');
                    const sourceIndex = this.getAttribute('data-source-index');
                    paginateDownloadItems(sourceIndex, action);
                });
            });
            
            // 下载资源每页显示数量选择
            const downloadGroupSelectors = document.querySelectorAll('.download-group-selector');
            downloadGroupSelectors.forEach(selector => {
                selector.addEventListener('change', function() {
                    const sourceIndex = this.getAttribute('data-source-index');
                    const itemsPerPage = parseInt(this.value);
                    updateDownloadPagination(sourceIndex, 1, itemsPerPage);
                });
            });
        }
        
        // 修复：初始化剧集搜索和分页功能
        function initializeEpisodeSearchAndPagination() {
            // 处理剧集搜索
            const episodeSearchInputs = document.querySelectorAll('.episode-search-input');
            const episodeSearchClears = document.querySelectorAll('.episode-search-clear');
            
            episodeSearchInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const sourceIndex = this.getAttribute('data-source-index');
                    const searchTerm = this.value.toLowerCase();
                    const clearBtn = document.querySelector('.episode-search-clear[data-source-index="' + sourceIndex + '"]');
                    
                    // 显示/隐藏清除按钮
                    if (searchTerm.length > 0) {
                        clearBtn.style.display = 'block';
                    } else {
                        clearBtn.style.display = 'none';
                    }
                    
                    // 过滤剧集
                    filterEpisodeItems(sourceIndex, searchTerm);
                });
            });
            
            // 剧集搜索清除按钮
            episodeSearchClears.forEach(btn => {
                btn.addEventListener('click', function() {
                    const sourceIndex = this.getAttribute('data-source-index');
                    const searchInput = document.querySelector('.episode-search-input[data-source-index="' + sourceIndex + '"]');
                    searchInput.value = '';
                    this.style.display = 'none';
                    filterEpisodeItems(sourceIndex, '');
                });
            });
            
            // 剧集分页按钮
            const episodePaginationBtns = document.querySelectorAll('.episode-pagination-btn');
            episodePaginationBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('data-action');
                    const sourceIndex = this.getAttribute('data-source-index');
                    paginateEpisodeItems(sourceIndex, action);
                });
            });
            
            // 剧集每页显示数量选择
            const episodeGroupSelectors = document.querySelectorAll('.episode-group-selector');
            episodeGroupSelectors.forEach(selector => {
                selector.addEventListener('change', function() {
                    const sourceIndex = this.getAttribute('data-source-index');
                    const itemsPerPage = parseInt(this.value);
                    updateEpisodePagination(sourceIndex, 1, itemsPerPage);
                });
            });
        }
        
        // 修复：过滤下载资源项
        function filterDownloadItems(sourceIndex, searchTerm) {
            const downloadList = document.getElementById('download-list-' + sourceIndex);
            const downloadItems = downloadList.querySelectorAll('.episode-download');
            let visibleCount = 0;
            
            downloadItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // 更新分页信息
            updateDownloadPagination(sourceIndex, 1, getDownloadItemsPerPage(sourceIndex), visibleCount);
        }
        
        // 修复：过滤剧集项
        function filterEpisodeItems(sourceIndex, searchTerm) {
            const episodeGrid = document.querySelector('.episode-grid[data-source-index="' + sourceIndex + '"]');
            const episodeItems = episodeGrid.querySelectorAll('.play-episode');
            let visibleCount = 0;
            
            episodeItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // 更新分页信息
            updateEpisodePagination(sourceIndex, 1, getEpisodeItemsPerPage(sourceIndex), visibleCount);
        }
        
        // 修复：下载资源分页
        function paginateDownloadItems(sourceIndex, action) {
            const itemsPerPage = getDownloadItemsPerPage(sourceIndex);
            const currentPage = getDownloadCurrentPage(sourceIndex);
            const totalItems = getDownloadTotalItems(sourceIndex);
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            let newPage = currentPage;
            
            if (action === 'prev' && currentPage > 1) {
                newPage = currentPage - 1;
            } else if (action === 'next' && currentPage < totalPages) {
                newPage = currentPage + 1;
            }
            
            updateDownloadPagination(sourceIndex, newPage, itemsPerPage);
        }
        
        // 修复：剧集分页
        function paginateEpisodeItems(sourceIndex, action) {
            const itemsPerPage = getEpisodeItemsPerPage(sourceIndex);
            const currentPage = getEpisodeCurrentPage(sourceIndex);
            const totalItems = getEpisodeTotalItems(sourceIndex);
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            let newPage = currentPage;
            
            if (action === 'prev' && currentPage > 1) {
                newPage = currentPage - 1;
            } else if (action === 'next' && currentPage < totalPages) {
                newPage = currentPage + 1;
            }
            
            updateEpisodePagination(sourceIndex, newPage, itemsPerPage);
        }
        
        // 修复：更新下载资源分页
        function updateDownloadPagination(sourceIndex, page, itemsPerPage, totalItems = null) {
            const downloadList = document.getElementById('download-list-' + sourceIndex);
            const downloadItems = downloadList.querySelectorAll('.episode-download');
            
            // 获取实际可见的项目
            const visibleItems = Array.from(downloadItems).filter(item => item.style.display !== 'none');
            
            // 如果没有传入总项目数，使用可见项目数
            if (totalItems === null) {
                totalItems = visibleItems.length;
            }
            
            // 计算总页数
            const totalPages = itemsPerPage === 0 ? 1 : Math.ceil(totalItems / itemsPerPage);
            
            // 确保页面在有效范围内
            page = Math.max(1, Math.min(page, totalPages));
            
            // 计算开始和结束索引
            const startIndex = itemsPerPage === 0 ? 0 : (page - 1) * itemsPerPage;
            const endIndex = itemsPerPage === 0 ? totalItems : Math.min(startIndex + itemsPerPage, totalItems);
            
            // 隐藏所有项目，然后显示当前页的项目
            visibleItems.forEach((item, index) => {
                if (index >= startIndex && index < endIndex) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // 更新分页信息显示
            const paginationInfo = document.querySelector('.download-pagination-info[data-source-index="' + sourceIndex + '"]');
            if (paginationInfo) {
                if (itemsPerPage === 0) {
                    paginationInfo.textContent = `全部 ${totalItems} 个`;
                } else {
                    paginationInfo.textContent = `${page}/${totalPages} (共 ${totalItems} 个)`;
                }
            }
            
            // 更新分页按钮状态
            const prevBtn = document.querySelector('.download-pagination-btn[data-action="prev"][data-source-index="' + sourceIndex + '"]');
            const nextBtn = document.querySelector('.download-pagination-btn[data-action="next"][data-source-index="' + sourceIndex + '"]');
            
            if (prevBtn) {
                prevBtn.disabled = page <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = page >= totalPages;
            }
            
            // 保存当前页面状态
            setDownloadCurrentPage(sourceIndex, page);
        }
        
        // 修复：更新剧集分页
        function updateEpisodePagination(sourceIndex, page, itemsPerPage, totalItems = null) {
            const episodeGrid = document.querySelector('.episode-grid[data-source-index="' + sourceIndex + '"]');
            const episodeItems = episodeGrid.querySelectorAll('.play-episode');
            
            // 获取实际可见的项目
            const visibleItems = Array.from(episodeItems).filter(item => item.style.display !== 'none');
            
            // 如果没有传入总项目数，使用可见项目数
            if (totalItems === null) {
                totalItems = visibleItems.length;
            }
            
            // 计算总页数
            const totalPages = itemsPerPage === 0 ? 1 : Math.ceil(totalItems / itemsPerPage);
            
            // 确保页面在有效范围内
            page = Math.max(1, Math.min(page, totalPages));
            
            // 计算开始和结束索引
            const startIndex = itemsPerPage === 0 ? 0 : (page - 1) * itemsPerPage;
            const endIndex = itemsPerPage === 0 ? totalItems : Math.min(startIndex + itemsPerPage, totalItems);
            
            // 隐藏所有项目，然后显示当前页的项目
            visibleItems.forEach((item, index) => {
                if (index >= startIndex && index < endIndex) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // 更新分页信息显示
            const paginationInfo = document.querySelector('.episode-pagination-info[data-source-index="' + sourceIndex + '"]');
            if (paginationInfo) {
                if (itemsPerPage === 0) {
                    paginationInfo.textContent = `全部 ${totalItems} 集`;
                } else {
                    paginationInfo.textContent = `${page}/${totalPages} (共 ${totalItems} 集)`;
                }
            }
            
            // 更新分页按钮状态
            const prevBtn = document.querySelector('.episode-pagination-btn[data-action="prev"][data-source-index="' + sourceIndex + '"]');
            const nextBtn = document.querySelector('.episode-pagination-btn[data-action="next"][data-source-index="' + sourceIndex + '"]');
            
            if (prevBtn) {
                prevBtn.disabled = page <= 1;
            }
            if (nextBtn) {
                nextBtn.disabled = page >= totalPages;
            }
            
            // 保存当前页面状态
            setEpisodeCurrentPage(sourceIndex, page);
        }
        
        // 辅助函数：获取下载资源每页显示数量
        function getDownloadItemsPerPage(sourceIndex) {
            const selector = document.querySelector('.download-group-selector[data-source-index="' + sourceIndex + '"]');
            return selector ? parseInt(selector.value) : DOWNLOAD_ITEMS_PER_PAGE;
        }
        
        // 辅助函数：获取剧集每页显示数量
        function getEpisodeItemsPerPage(sourceIndex) {
            const selector = document.querySelector('.episode-group-selector[data-source-index="' + sourceIndex + '"]');
            return selector ? parseInt(selector.value) : 20;
        }
        
        // 辅助函数：获取下载资源当前页面
        function getDownloadCurrentPage(sourceIndex) {
            return parseInt(sessionStorage.getItem('download_page_' + sourceIndex)) || 1;
        }
        
        // 辅助函数：获取剧集当前页面
        function getEpisodeCurrentPage(sourceIndex) {
            return parseInt(sessionStorage.getItem('episode_page_' + sourceIndex)) || 1;
        }
        
        // 辅助函数：设置下载资源当前页面
        function setDownloadCurrentPage(sourceIndex, page) {
            sessionStorage.setItem('download_page_' + sourceIndex, page);
        }
        
        // 辅助函数：设置剧集当前页面
        function setEpisodeCurrentPage(sourceIndex, page) {
            sessionStorage.setItem('episode_page_' + sourceIndex, page);
        }
        
        // 辅助函数：获取下载资源总项目数
        function getDownloadTotalItems(sourceIndex) {
            const downloadList = document.getElementById('download-list-' + sourceIndex);
            if (!downloadList) return 0;
            
            const downloadItems = downloadList.querySelectorAll('.episode-download');
            return downloadItems.length;
        }
        
        // 辅助函数：获取剧集总项目数
        function getEpisodeTotalItems(sourceIndex) {
            const episodeGrid = document.querySelector('.episode-grid[data-source-index="' + sourceIndex + '"]');
            if (!episodeGrid) return 0;
            
            const episodeItems = episodeGrid.querySelectorAll('.play-episode');
            return episodeItems.length;
        }
        
        // 初始化所有下载资源和剧集的分页
        function initializeAllPagination() {
            // 初始化下载资源分页
            const downloadControls = document.querySelectorAll('.download-controls');
            downloadControls.forEach(control => {
                const sourceIndex = control.id.split('-')[2];
                updateDownloadPagination(sourceIndex, 1, getDownloadItemsPerPage(sourceIndex));
            });
            
            // 初始化剧集分页
            const episodeControls = document.querySelectorAll('.episode-controls');
            episodeControls.forEach(control => {
                const sourceIndex = control.querySelector('.episode-search-input').getAttribute('data-source-index');
                updateEpisodePagination(sourceIndex, 1, getEpisodeItemsPerPage(sourceIndex));
            });
        }
        
        // 页面加载完成后初始化分页
        document.addEventListener('DOMContentLoaded', function() {
            initializeAllPagination();
        });

        // 播放剧集功能
        const playEpisodeButtons = document.querySelectorAll('.play-episode');
        const playerModal = document.getElementById('player-modal');
        const closePlayer = document.getElementById('close-player');
        const videoPlayer = document.getElementById('video-player');
        const prevEpisodeBtn = document.getElementById('prev-episode');
        const nextEpisodeBtn = document.getElementById('next-episode');
        const shareEpisodeBtn = document.getElementById('share-episode');
        const videoError = document.getElementById('video-error');
        
        let currentEpisodeIndex = 0;
        let currentSourceIndex = 0;
        let currentEpisodes = [];
        
        playEpisodeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const episodeUrl = this.getAttribute('data-url');
                const vodId = this.getAttribute('data-vod-id');
                const vodName = this.getAttribute('data-vod-name');
                const episodeName = this.getAttribute('data-episode-name');
                const vodPic = this.getAttribute('data-vod-pic');
                const sourceIndex = this.getAttribute('data-source-index');
                const episodeIndex = parseInt(this.getAttribute('data-episode-index'));
                
                // 获取当前数据源的所有剧集
                currentSourceIndex = sourceIndex;
                currentEpisodeIndex = episodeIndex;
                currentEpisodes = Array.from(document.querySelectorAll('.play-episode[data-source-index="' + sourceIndex + '"]'));
                
                // 更新上下集按钮状态
                updateEpisodeControls();
                
                // 播放视频
                playVideo(episodeUrl, vodId, vodName, episodeName, vodPic);
            });
        });
        
        // 关闭播放器
        closePlayer.addEventListener('click', function() {
            playerModal.style.display = 'none';
            videoPlayer.src = '';
            videoError.style.display = 'none';
        });
        
        // 上一集
        prevEpisodeBtn.addEventListener('click', function() {
            if (currentEpisodeIndex > 0) {
                const prevEpisode = currentEpisodes[currentEpisodeIndex - 1];
                prevEpisode.click();
            }
        });
        
        // 下一集
        nextEpisodeBtn.addEventListener('click', function() {
            if (currentEpisodeIndex < currentEpisodes.length - 1) {
                const nextEpisode = currentEpisodes[currentEpisodeIndex + 1];
                nextEpisode.click();
            }
        });
        
        // 分享当前剧集
        shareEpisodeBtn.addEventListener('click', function() {
            const currentEpisode = currentEpisodes[currentEpisodeIndex];
            const episodeUrl = currentEpisode.getAttribute('data-url');
            const vodName = currentEpisode.getAttribute('data-vod-name');
            const episodeName = currentEpisode.getAttribute('data-episode-name');
            
            const shareUrl = window.location.origin + window.location.pathname + '?vod_id=' + currentEpisode.getAttribute('data-vod-id');
            const shareTitle = vodName + ' - ' + episodeName;
            
            openShareModal(shareUrl, shareTitle);
        });
        
        // 更新剧集控制按钮状态
        function updateEpisodeControls() {
            prevEpisodeBtn.disabled = currentEpisodeIndex <= 0;
            nextEpisodeBtn.disabled = currentEpisodeIndex >= currentEpisodes.length - 1;
        }
        
        // 播放视频
        function playVideo(episodeUrl, vodId, vodName, episodeName, vodPic) {
            // 获取选择的解析器
            const parserSelect = document.getElementById('parser-select');
            const selectedParser = parserSelect ? parserSelect.value : '';
            const parserUrl = parserSelect && selectedParser ? parserSelect.options[parserSelect.selectedIndex].getAttribute('data-url') : '';
            
            // 获取选择的去插播接口
            const adRemovalSelect = document.getElementById('ad-removal-select');
            const selectedAdRemoval = adRemovalSelect ? adRemovalSelect.value : '';
            const adRemovalUrl = adRemovalSelect && selectedAdRemoval ? adRemovalSelect.options[adRemovalSelect.selectedIndex].getAttribute('data-url') : '';
            
            let finalUrl = episodeUrl;
            
            // 应用去插播接口（优先）
            if (selectedAdRemoval && adRemovalUrl) {
                finalUrl = adRemovalUrl + encodeURIComponent(episodeUrl);
            }
            // 应用解析器
            else if (selectedParser && parserUrl) {
                finalUrl = parserUrl + encodeURIComponent(episodeUrl);
            }
            
            // 显示播放器
            playerModal.style.display = 'flex';
            videoError.style.display = 'none';
            
            // 设置视频源
            videoPlayer.src = finalUrl;
            
            // 添加到观看历史
            addToHistory(vodId, vodName, episodeName, episodeUrl, vodPic);
        }
        
        // 添加到观看历史
        function addToHistory(vodId, vodName, episodeName, episodeUrl, vodPic) {
            const formData = new FormData();
            formData.append('action', 'add_to_history');
            formData.append('vod_id', vodId);
            formData.append('vod_name', vodName);
            formData.append('episode_name', episodeName);
            formData.append('episode_url', episodeUrl);
            formData.append('vod_pic', vodPic);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('添加到历史记录失败:', data.message);
                }
            })
            .catch(error => {
                console.error('添加到历史记录出错:', error);
            });
        }
        
        // 打开分享模态框
        function openShareModal(url, title) {
            const shareModal = document.getElementById('share-modal');
            const shareUrlInput = document.getElementById('share-url');
            
            shareUrlInput.value = url;
            shareModal.style.display = 'flex';
            
            // 设置分享选项
            const shareOptions = document.querySelectorAll('.share-option');
            shareOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const shareType = this.getAttribute('data-share');
                    shareToPlatform(shareType, url, title);
                });
            });
        }
        
        // 关闭分享模态框
        const closeShare = document.getElementById('close-share');
        if (closeShare) {
            closeShare.addEventListener('click', function() {
                const shareModal = document.getElementById('share-modal');
                shareModal.style.display = 'none';
            });
        }
        
        // 复制链接
        const copyLink = document.getElementById('copy-link');
        if (copyLink) {
            copyLink.addEventListener('click', function() {
                const shareUrl = document.getElementById('share-url');
                shareUrl.select();
                document.execCommand('copy');
                
                // 显示复制成功提示
                const originalText = copyLink.innerHTML;
                copyLink.innerHTML = '<i class="bi bi-check"></i> 已复制';
                setTimeout(() => {
                    copyLink.innerHTML = originalText;
                }, 2000);
            });
        }
        
        // 分享到不同平台
        function shareToPlatform(platform, url, title) {
            const encodedUrl = encodeURIComponent(url);
            const encodedTitle = encodeURIComponent(title);
            
            let shareUrl = '';
            
            switch (platform) {
                case 'weibo':
                    shareUrl = `https://service.weibo.com/share/share.php?url=${encodedUrl}&title=${encodedTitle}`;
                    break;
                case 'wechat':
                    // 微信分享需要特殊处理，这里只是简单提示
                    alert('请使用微信内置浏览器打开此页面，然后使用微信分享功能');
                    return;
                case 'qq':
                    shareUrl = `https://connect.qq.com/widget/shareqq/index.html?url=${encodedUrl}&title=${encodedTitle}`;
                    break;
                case 'qzone':
                    shareUrl = `https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=${encodedUrl}&title=${encodedTitle}`;
                    break;
                case 'douban':
                    shareUrl = `https://www.douban.com/share/service?url=${encodedUrl}&title=${encodedTitle}`;
                    break;
                case 'link':
                    // 链接已经通过复制功能处理
                    return;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        }
        
        // 视频错误处理
        videoPlayer.addEventListener('error', function() {
            videoError.style.display = 'block';
        });
        
        // 尝试外部播放器
        document.getElementById('try-external').addEventListener('click', function() {
            const currentEpisode = currentEpisodes[currentEpisodeIndex];
            const episodeUrl = currentEpisode.getAttribute('data-url');
            window.open(episodeUrl, '_blank');
        });
        
        // 尝试下载
        document.getElementById('try-download').addEventListener('click', function() {
            const currentEpisode = currentEpisodes[currentEpisodeIndex];
            const episodeUrl = currentEpisode.getAttribute('data-url');
            window.open(episodeUrl, '_blank');
        });
        
        // 刷新视频
        document.getElementById('refresh-video').addEventListener('click', function() {
            const currentEpisode = currentEpisodes[currentEpisodeIndex];
            const episodeUrl = currentEpisode.getAttribute('data-url');
            playVideo(episodeUrl, 
                     currentEpisode.getAttribute('data-vod-id'),
                     currentEpisode.getAttribute('data-vod-name'),
                     currentEpisode.getAttribute('data-episode-name'),
                     currentEpisode.getAttribute('data-vod-pic'));
        });
    </script>
</body>
</html>