<?php
/*
Name: OK Quiz System (HQ & Emoji Fix)
Description: დამატებულია: თავისუფალი ტექსტი, 1200x630 HQ ხარისხი, ემოჯების ფილტრაცია სურათზე და შორთკოდის ჩვენება ადმინში.
Version: 36.0
Author: OK Engine
*/

// --- Helper: Emoji Cleaner (სურათისთვის) ---
function ok_clean_text_for_img($text) {
    return preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);
}

// --- 1. META TAGS გენერატორი ---
function ok_quiz_get_meta_tags() {
    if (!isset($_GET['ok_chk_share']) || $_GET['ok_chk_share'] != '1') return '';

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');
    
    $params = $_GET;
    $params['ok_action'] = 'share_img'; 
    unset($params['ok_chk_share']);
    $image_url = $base_url . '?' . http_build_query($params);
    
    $page_params = $_GET;
    if(isset($page_params['ok_action'])) unset($page_params['ok_action']);
    $page_url = $base_url . '?' . http_build_query($page_params);

    $title = isset($_GET['title']) ? htmlspecialchars(urldecode($_GET['title'])) : 'Quiz Result';
    $msg   = isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'ნახე ჩემი შედეგი!';
    
    $w     = isset($_GET['w']) ? htmlspecialchars($_GET['w']) : 1200;
    $h     = isset($_GET['h']) ? htmlspecialchars($_GET['h']) : 630;

    $html  = "\n\n";
    $html .= '<meta property="og:type" content="article" />' . "\n";
    $html .= '<meta property="og:title" content="' . $title . '" />' . "\n";
    $html .= '<meta property="og:description" content="' . $msg . '" />' . "\n";
    $html .= '<meta property="og:url" content="' . $page_url . '" />' . "\n";
    $html .= '<meta property="og:image" content="' . $image_url . '" />' . "\n";
    $html .= '<meta property="og:image:width" content="' . $w . '" />' . "\n";
    $html .= '<meta property="og:image:height" content="' . $h . '" />' . "\n";
    $html .= '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    $html .= '<meta name="twitter:image" content="' . $image_url . '" />' . "\n";

    return $html;
}

// --- 2. ფოტოს გენერატორი ---
if (isset($_GET['ok_action']) && $_GET['ok_action'] === 'share_img') {
    if (ob_get_length()) ob_clean();

    $score = isset($_GET['s']) ? intval($_GET['s']) : 0;
    $total = isset($_GET['t']) ? intval($_GET['t']) : 0;
    
    $title_txt = isset($_GET['title']) ? ok_clean_text_for_img(urldecode($_GET['title'])) : '';
    $msg_txt   = isset($_GET['msg']) ? ok_clean_text_for_img(urldecode($_GET['msg'])) : '';
    $cust_txt  = isset($_GET['ct']) ? ok_clean_text_for_img(urldecode($_GET['ct'])) : ''; 

    $W = isset($_GET['w']) ? intval($_GET['w']) : 1200;
    $H = isset($_GET['h']) ? intval($_GET['h']) : 630;
    $bg_type = isset($_GET['bgt']) ? $_GET['bgt'] : 'col';
    $bg_val  = isset($_GET['bgv']) ? urldecode($_GET['bgv']) : 'ffffff';

    $conf = [
        'c' => ['show'=>$_GET['c_sh']??0,'x'=>$_GET['c_x']??'','y'=>$_GET['c_y']??50,'sz'=>$_GET['c_sz']??20,'c'=>$_GET['c_c']??'555','txt'=>$cust_txt],
        't' => ['show'=>$_GET['t_sh']??1,'x'=>$_GET['t_x']??'','y'=>$_GET['t_y']??120,'sz'=>$_GET['t_sz']??30,'c'=>$_GET['t_c']??'000','txt'=>$title_txt],
        's' => ['show'=>$_GET['s_sh']??1,'x'=>$_GET['s_x']??'','y'=>$_GET['s_y']??280,'sz'=>$_GET['s_sz']??80,'c'=>$_GET['s_c']??'0d6efd','txt'=>$score.' / '.$total],
        'm' => ['show'=>$_GET['m_sh']??1,'x'=>$_GET['m_x']??'','y'=>$_GET['m_y']??450,'sz'=>$_GET['m_sz']??25,'c'=>$_GET['m_c']??'333','txt'=>$msg_txt]
    ];

    function ok_hex2rgb($hex) {
        $hex = str_replace("#", "", $hex);
        if(strlen($hex) == 3) { $r = hexdec(substr($hex,0,1).substr($hex,0,1)); $g = hexdec(substr($hex,1,1).substr($hex,1,1)); $b = hexdec(substr($hex,2,1).substr($hex,2,1)); } 
        else { $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2)); }
        return [$r, $g, $b];
    }

    $im = imagecreatetruecolor($W, $H);
    $fontPath = __DIR__ . '/font.ttf';
    $hasFont = file_exists($fontPath);

    if ($bg_type === 'img' && !empty($bg_val) && ini_get('allow_url_fopen')) {
        $ext = strtolower(pathinfo(parse_url($bg_val, PHP_URL_PATH), PATHINFO_EXTENSION));
        $src = null;
        if (strpos($ext, 'jpg')!==false || strpos($ext, 'jpeg')!==false) @$src = imagecreatefromjpeg($bg_val);
        elseif (strpos($ext, 'png')!==false) @$src = imagecreatefrompng($bg_val);
        if ($src) { imagecopyresampled($im, $src, 0, 0, 0, 0, $W, $H, imagesx($src), imagesy($src)); imagedestroy($src); } 
        else { imagefilledrectangle($im, 0, 0, $W, $H, imagecolorallocate($im, 255, 255, 255)); }
    } else {
        $rgb = ok_hex2rgb($bg_val);
        imagefilledrectangle($im, 0, 0, $W, $H, imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]));
    }

    foreach ($conf as $el) {
        if ($el['show'] != 1 || empty($el['txt'])) continue;
        $rgb = ok_hex2rgb($el['c']);
        $color = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
        $text = $el['txt'];
        $size = intval($el['sz']);
        $baseY = intval($el['y']);
        
        if ($hasFont) {
            $maxW = $W - 60; 
            $words = explode(' ', $text);
            $lines = [];
            $currLine = '';
            foreach ($words as $word) {
                $testLine = $currLine . ($currLine ? ' ' : '') . $word;
                $box = imagettfbbox($size, 0, $fontPath, $testLine);
                if (($box[2] - $box[0]) > $maxW && $currLine !== '') {
                    $lines[] = $currLine;
                    $currLine = $word;
                } else {
                    $currLine = $testLine;
                }
            }
            $lines[] = $currLine; 

            $lineHeight = $size * 1.6; 
            foreach ($lines as $i => $line) {
                $box = imagettfbbox($size, 0, $fontPath, $line);
                $textW = $box[2] - $box[0];
                $drawX = ($el['x'] === '' || $el['x'] === null) ? intval(($W - $textW) / 2) : intval($el['x']);
                $drawY = intval($baseY + ($i * $lineHeight));
                imagettftext($im, $size, 0, $drawX, $drawY, $color, $fontPath, $line);
            }
        } else {
            $x = ($el['x'] === '' || $el['x'] === null) ? intval(($W - (strlen($text) * imagefontwidth(5))) / 2) : intval($el['x']);
            imagestring($im, 5, $x, $baseY - 15, $text, $color);
        }
    }
    header('Content-Type: image/png'); imagepng($im); imagedestroy($im); exit;
}

// --- 3. ბაზის ფუნქციები ---
define('OK_QUIZ_DB', __DIR__ . '/quiz-data.json');
function ok_quiz_get_all() { if (!file_exists(OK_QUIZ_DB)) return []; return json_decode(file_get_contents(OK_QUIZ_DB), true) ?? []; }
function ok_quiz_get($id) { return ok_quiz_get_all()[$id] ?? false; }
function ok_quiz_save($id, $data) { $db=ok_quiz_get_all(); $db[$id]=$data; file_put_contents(OK_QUIZ_DB, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); }
function ok_quiz_delete($id) { $db=ok_quiz_get_all(); unset($db[$id]); file_put_contents(OK_QUIZ_DB, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); }

// --- 4. ადმინ პანელი ---
function ok_render_quiz_admin_page() {
    $action = $_GET['action'] ?? 'list';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quiz'])) {
        $id = $_POST['quiz_id'] ?: uniqid();
        $share = [
            'w' => $_POST['sh_w'], 'h' => $_POST['sh_h'], 'bg_type'=> $_POST['sh_bg_type'], 'bg_val' => $_POST['sh_bg_val'],
            'c_sh'=>isset($_POST['sh_c_sh'])?1:0, 'c_txt'=>$_POST['sh_c_txt'], 'c_x'=>$_POST['sh_c_x'], 'c_y'=>$_POST['sh_c_y'], 'c_sz'=>$_POST['sh_c_sz'], 'c_c'=>$_POST['sh_c_c'],
            't_sh'=>isset($_POST['sh_t_sh'])?1:0, 't_x'=>$_POST['sh_t_x'], 't_y'=>$_POST['sh_t_y'], 't_sz'=>$_POST['sh_t_sz'], 't_c'=>$_POST['sh_t_c'],
            's_sh'=>isset($_POST['sh_s_sh'])?1:0, 's_x'=>$_POST['sh_s_x'], 's_y'=>$_POST['sh_s_y'], 's_sz'=>$_POST['sh_s_sz'], 's_c'=>$_POST['sh_s_c'],
            'm_sh'=>isset($_POST['sh_m_sh'])?1:0, 'm_x'=>$_POST['sh_m_x'], 'm_y'=>$_POST['sh_m_y'], 'm_sz'=>$_POST['sh_m_sz'], 'm_c'=>$_POST['sh_m_c'],
        ];

        $questions = [];
        if (isset($_POST['q_text']) && is_array($_POST['q_text'])) {
            foreach ($_POST['q_text'] as $key => $q_txt) {
                $img = $_POST['q_img'][$key] ?? '';
                $correct = $_POST['q_correct'][$key] ?? 0;
                $optsRaw = $_POST['q_opt'][$key] ?? [];
                if (!is_array($optsRaw)) $optsRaw = []; 
                $opts = array_values(array_filter($optsRaw, function($val){ return !empty(trim($val)); }));
                $questions[] = ['q' => $q_txt, 'img' => $img, 'options' => $opts, 'correct' => (int)$correct];
            }
        }

        $results = [];
        if (isset($_POST['res_min'])) {
            foreach ($_POST['res_min'] as $i => $min) {
                if ($_POST['res_msg'][$i] != '') $results[] = ['min' => (int)$min, 'max' => (int)$_POST['res_max'][$i], 'msg' => $_POST['res_msg'][$i]];
            }
        }
        ok_quiz_save($id, ['title' => $_POST['quiz_title'], 'cover_img' => $_POST['quiz_cover_img'] ?? '', 'share_config' => $share, 'questions' => $questions, 'results' => $results]);
        echo '<script>window.location.href = window.location.href + "&msg=saved";</script>'; return; 
    }

    if ($action === 'delete') { ok_quiz_delete($_GET['id']); echo '<script>window.location.href = "?page=ok-quiz&msg=deleted";</script>'; return; }

    $edit_data = ($action === 'edit' && isset($_GET['id'])) ? ok_quiz_get($_GET['id']) : null;
    $edit_id = $_GET['id'] ?? '';
    $current_results = $edit_data['results'] ?? [['min'=>0, 'max'=>100, 'msg'=>'შენი შედეგია!']];
    $sh = $edit_data['share_config'] ?? [];
    $gv = function($k, $def) use ($sh) { return isset($sh[$k]) ? $sh[$k] : $def; };
    $defW = $gv('w', 1200); 
    $defH = $gv('h', 630);
    ?>
    <div class="wrap p-4">
        <h2 class="fw-bold mb-4">ქვიზების მართვა</h2>
        <div class="row">
            <div class="col-md-3">
                <div class="list-group shadow-sm">
                    <?php foreach(ok_quiz_get_all() as $qid => $qdata): ?>
                        <div class="list-group-item d-flex flex-column align-items-start py-3">
                            <div class="d-flex justify-content-between align-items-center w-100 mb-2">
                                <div class="text-truncate" style="max-width:140px;"><strong><?php echo htmlspecialchars($qdata['title']); ?></strong></div>
                                <div><a href="?page=ok-quiz&action=edit&id=<?php echo $qid; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a><a href="?page=ok-quiz&action=delete&id=<?php echo $qid; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('წავშალო?')"><i class="bi bi-trash"></i></a></div>
                            </div>
                            <div class="w-100">
                                <code class="small d-block p-1 bg-light border text-primary" style="user-select: all; word-break: break-all;">[ok_quiz id="<?php echo $qid; ?>"]</code>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="?page=ok-quiz" class="list-group-item list-group-item-action active text-center">+ ახალი ქვიზი</a>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card shadow-sm"><div class="card-header bg-white py-3"><h5 class="m-0 fw-bold text-primary">ქვიზის რედაქტირება</h5></div><div class="card-body">
                    <form method="POST" id="quizForm">
                        <input type="hidden" name="save_quiz" value="1"><input type="hidden" name="quiz_id" value="<?php echo $edit_id; ?>">
                        <div class="row mb-4"><div class="col-md-6"><label class="fw-bold form-label">სათაური</label><input type="text" name="quiz_title" id="inp_title" class="form-control" required value="<?php echo $edit_data['title'] ?? ''; ?>"></div><div class="col-md-6"><label class="fw-bold form-label">მთავარი ფოტო</label><input type="text" name="quiz_cover_img" class="form-control" value="<?php echo $edit_data['cover_img'] ?? ''; ?>"></div></div>
                        
                        <div class="card mb-4 border-warning border-opacity-25 bg-warning bg-opacity-10">
                            <div class="card-header bg-warning bg-opacity-25 fw-bold d-flex justify-content-between align-items-center"><span><i class="bi bi-palette"></i> გაზიარების დიზაინი</span><button type="button" class="btn btn-sm btn-dark" onclick="updatePreview()">🔄 განაახლე ვიზუალი</button></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-7 border-end">
                                        <div class="row g-3 mb-3 pb-3 border-bottom border-warning border-opacity-25">
                                            <div class="col-md-2"><label class="small fw-bold">სიგანე</label><input type="number" id="sh_w" name="sh_w" class="form-control form-control-sm" value="<?php echo $defW; ?>"></div>
                                            <div class="col-md-2"><label class="small fw-bold">სიმაღლე</label><input type="number" id="sh_h" name="sh_h" class="form-control form-control-sm" value="<?php echo $defH; ?>"></div>
                                            <div class="col-md-2"><label class="small fw-bold">ფონი</label><select id="sh_bg_type" name="sh_bg_type" class="form-select form-select-sm"><option value="col" <?php if($gv('bg_type','col')=='col') echo 'selected'; ?>>ფერი</option><option value="img" <?php if($gv('bg_type','col')=='img') echo 'selected'; ?>>სურათი</option></select></div>
                                            <div class="col-md-6"><label class="small fw-bold">HEX / URL</label><input type="text" id="sh_bg_val" name="sh_bg_val" class="form-control form-control-sm" value="<?php echo $gv('bg_val', '#ffffff'); ?>"></div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless align-middle">
                                                <thead><tr class="small text-muted text-uppercase"><th>ელემენტი</th><th>ჩართვა</th><th>X</th><th>Y</th><th>ზომა</th><th>ფერი</th></tr></thead>
                                                <tbody>
                                                    <tr class="bg-white bg-opacity-50 border-bottom">
                                                        <td><span class="fw-bold d-block small mb-1 text-success">თავისუფალი</span><input type="text" id="sh_c_txt" name="sh_c_txt" class="form-control form-control-sm" placeholder="ტექსტი..." value="<?php echo htmlspecialchars($gv('c_txt','')); ?>"></td>
                                                        <td><input class="form-check-input" type="checkbox" id="sh_c_sh" name="sh_c_sh" <?php if($gv('c_sh',0)) echo 'checked'; ?>></td>
                                                        <td><input type="number" id="sh_c_x" name="sh_c_x" class="form-control form-control-sm" placeholder="Auto" value="<?php echo $gv('c_x', ''); ?>"></td>
                                                        <td><input type="number" id="sh_c_y" name="sh_c_y" class="form-control form-control-sm" value="<?php echo $gv('c_y', 50); ?>"></td>
                                                        <td><input type="number" id="sh_c_sz" name="sh_c_sz" class="form-control form-control-sm" value="<?php echo $gv('c_sz', 20); ?>"></td>
                                                        <td><input type="color" id="sh_c_c" name="sh_c_c" class="form-control form-control-color" value="<?php echo $gv('c_c', '#555555'); ?>"></td>
                                                    </tr>
                                                    <tr><td class="fw-bold pt-3">სათაური</td><td class="pt-3"><input class="form-check-input" type="checkbox" id="sh_t_sh" name="sh_t_sh" <?php if($gv('t_sh',1)) echo 'checked'; ?>></td><td class="pt-3"><input type="number" id="sh_t_x" name="sh_t_x" class="form-control form-control-sm" placeholder="Auto" value="<?php echo $gv('t_x', ''); ?>"></td><td class="pt-3"><input type="number" id="sh_t_y" name="sh_t_y" class="form-control form-control-sm" value="<?php echo $gv('t_y', 120); ?>"></td><td class="pt-3"><input type="number" id="sh_t_sz" name="sh_t_sz" class="form-control form-control-sm" value="<?php echo $gv('t_sz', 30); ?>"></td><td class="pt-3"><input type="color" id="sh_t_c" name="sh_t_c" class="form-control form-control-color" value="<?php echo $gv('t_c', '#000000'); ?>"></td></tr>
                                                    <tr><td class="fw-bold">ქულა</td><td><input class="form-check-input" type="checkbox" id="sh_s_sh" name="sh_s_sh" <?php if($gv('s_sh',1)) echo 'checked'; ?>></td><td><input type="number" id="sh_s_x" name="sh_s_x" class="form-control form-control-sm" placeholder="Auto" value="<?php echo $gv('s_x', ''); ?>"></td><td><input type="number" id="sh_s_y" name="sh_s_y" class="form-control form-control-sm" value="<?php echo $gv('s_y', 280); ?>"></td><td><input type="number" id="sh_s_sz" name="sh_s_sz" class="form-control form-control-sm" value="<?php echo $gv('s_sz', 80); ?>"></td><td><input type="color" id="sh_s_c" name="sh_s_c" class="form-control form-control-color" value="<?php echo $gv('s_c', '#0d6efd'); ?>"></td></tr>
                                                    <tr><td class="fw-bold">შედეგი</td><td><input class="form-check-input" type="checkbox" id="sh_m_sh" name="sh_m_sh" <?php if($gv('m_sh',1)) echo 'checked'; ?>></td><td><input type="number" id="sh_m_x" name="sh_m_x" class="form-control form-control-sm" placeholder="Auto" value="<?php echo $gv('m_x', ''); ?>"></td><td><input type="number" id="sh_m_y" name="sh_m_y" class="form-control form-control-sm" value="<?php echo $gv('m_y', 450); ?>"></td><td><input type="number" id="sh_m_sz" name="sh_m_sz" class="form-control form-control-sm" value="<?php echo $gv('m_sz', 25); ?>"></td><td><input type="color" id="sh_m_c" name="sh_m_c" class="form-control form-control-color" value="<?php echo $gv('m_c', '#333333'); ?>"></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 text-center">
                                        <div class="border rounded bg-white p-2 d-flex align-items-center justify-content-center" style="min-height: 200px; background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAGElEQVQYlWNgYGCQwoKxgqGgcJA5h3yFAAs8BRWVSwooAAAAAElFTkSuQmCC');">
                                            <img id="share-preview-img" src="" class="img-fluid shadow-sm" style="max-height:300px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold border-bottom pb-2">შედეგები</h5>
                        <div id="results-container"><?php foreach($current_results as $r): ?><div class="row g-2 mb-2 res-row align-items-center"><div class="col-2"><input type="number" name="res_min[]" class="form-control form-control-sm" placeholder="Min" value="<?php echo $r['min']; ?>"></div><div class="col-2"><input type="number" name="res_max[]" class="form-control form-control-sm" placeholder="Max" value="<?php echo $r['max']; ?>"></div><div class="col"><input type="text" name="res_msg[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($r['msg']); ?>"></div><div class="col-auto"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.res-row').remove()">X</button></div></div><?php endforeach; ?></div><button type="button" class="btn btn-sm btn-outline-secondary mb-4" onclick="addResultRow()">+ შედეგი</button>
                        
                        <h5 class="fw-bold border-bottom pb-2">კითხვები</h5>
                        <div id="questions-container">
                            <?php $qs = $edit_data['questions'] ?? [['q'=>'','img'=>'','options'=>['','','',''],'correct'=>0]]; foreach($qs as $idx => $q): ?>
                                <div class="q-card mb-3 p-3 border rounded bg-light position-relative">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="this.parentElement.remove()">X</button>
                                    <div class="mb-2"><input type="text" name="q_text[<?php echo $idx; ?>]" class="form-control" value="<?php echo htmlspecialchars($q['q']); ?>" placeholder="კითხვა"></div>
                                    <div class="mb-2"><input type="text" name="q_img[<?php echo $idx; ?>]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($q['img'] ?? ''); ?>" placeholder="IMG URL"></div>
                                    <div class="row g-2">
                                    <?php for($o=0; $o<4; $o++): ?>
                                        <div class="col-md-6"><div class="input-group input-group-sm">
                                            <div class="input-group-text"><input type="radio" name="temp_<?php echo $idx; ?>" value="<?php echo $o; ?>" <?php if(($q['correct']??0)==$o) echo 'checked'; ?> onchange="this.closest('.q-card').querySelector('.real-correct').value=this.value"></div>
                                            <input type="text" name="q_opt[<?php echo $idx; ?>][]" class="form-control" value="<?php echo htmlspecialchars($q['options'][$o] ?? ''); ?>">
                                        </div></div>
                                    <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="q_correct[<?php echo $idx; ?>]" class="real-correct" value="<?php echo $q['correct'] ?? 0; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary w-100 mb-2" onclick="addQuestion()">+ კითხვა</button>
                        <hr><button type="submit" class="btn btn-success btn-lg w-100"><i class="bi bi-save"></i> შენახვა</button>
                    </form></div></div></div></div></div>
    <script>
        function addQuestion() { 
            let c=document.getElementById('questions-container'), idx=Date.now(); 
            c.insertAdjacentHTML('beforeend',`
                <div class="q-card mb-3 p-3 border rounded bg-light position-relative">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="this.parentElement.remove()">X</button>
                    <div class="mb-2"><input type="text" name="q_text[${idx}]" class="form-control" placeholder="კითხვა"></div>
                    <div class="mb-2"><input type="text" name="q_img[${idx}]" class="form-control form-control-sm" placeholder="IMG URL"></div>
                    <div class="row g-2">
                        ${[0,1,2,3].map(i=>`
                            <div class="col-md-6"><div class="input-group input-group-sm">
                                <div class="input-group-text"><input type="radio" name="temp_${idx}" value="${i}" ${i===0?'checked':''} onchange="this.closest('.q-card').querySelector('.real-correct').value=this.value"></div>
                                <input type="text" name="q_opt[${idx}][]" class="form-control">
                            </div></div>
                        `).join('')}
                    </div>
                    <input type="hidden" name="q_correct[${idx}]" class="real-correct" value="0">
                </div>
            `); 
        }
        function addResultRow() { document.getElementById('results-container').insertAdjacentHTML('beforeend',`<div class="row g-2 mb-2 res-row align-items-center"><div class="col-2"><input type="number" name="res_min[]" class="form-control form-control-sm" placeholder="Min"></div><div class="col-2"><input type="number" name="res_max[]" class="form-control form-control-sm" placeholder="Max"></div><div class="col"><input type="text" name="res_msg[]" class="form-control form-control-sm" placeholder="ტექსტი"></div><div class="col-auto"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.res-row').remove()">X</button></div></div>`); }
        function updatePreview() {
            let url = window.location.href.split('?')[0] + '?page=ok-quiz&ok_action=share_img';
            const g = (id) => { let el = document.getElementById(id); if(el.type === 'checkbox') return el.checked ? 1 : 0; return encodeURIComponent(el.value); };
            url += '&s=8&t=10&title=' + g('inp_title') + '&msg=' + encodeURIComponent('მაგალითი: შედეგი!');
            url += '&w=' + g('sh_w') + '&h=' + g('sh_h') + '&bgt=' + g('sh_bg_type') + '&bgv=' + g('sh_bg_val');
            url += '&ct=' + g('sh_c_txt') + '&c_sh=' + g('sh_c_sh') + '&c_x=' + g('sh_c_x') + '&c_y=' + g('sh_c_y') + '&c_sz=' + g('sh_c_sz') + '&c_c=' + g('sh_c_c').replace('%23','');
            url += '&t_sh=' + g('sh_t_sh') + '&t_x=' + g('sh_t_x') + '&t_y=' + g('sh_t_y') + '&t_sz=' + g('sh_t_sz') + '&t_c=' + g('sh_t_c').replace('%23','');
            url += '&s_sh=' + g('sh_s_sh') + '&s_x=' + g('sh_s_x') + '&s_y=' + g('sh_s_y') + '&s_sz=' + g('sh_s_sz') + '&s_c=' + g('sh_s_c').replace('%23','');
            url += '&m_sh=' + g('sh_m_sh') + '&m_x=' + g('sh_m_x') + '&m_y=' + g('sh_m_y') + '&m_sz=' + g('sh_m_sz') + '&m_c=' + g('sh_m_c').replace('%23','');
            let img = document.getElementById('share-preview-img'); img.style.opacity = 0.5; img.src = url + '&rand=' + Math.random(); img.onload = function() { img.style.opacity = 1; };
        }
        setTimeout(updatePreview, 1000);
    </script>
    <?php
}

// --- 5. Frontend ---
function ok_quiz_render($atts) {
    $id = $atts['id'] ?? '';
    $data = ok_quiz_get($id);
    if (!$data) return '';
    $uid = 'ok_quiz_'.uniqid();
    $json = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
    ob_start(); ?>
    <div id="<?php echo $uid; ?>" class="ok-quiz-wrapper" data-quiz="<?php echo $json; ?>">
        <div class="ok-quiz-start ok-step active">
            <div class="ok-quiz-card">
                <?php if (!empty($data['cover_img'])): ?><div class="ok-img-header"><img src="<?php echo htmlspecialchars($data['cover_img']); ?>" class="ok-full-img"></div><?php endif; ?>
                <div class="ok-card-body text-center">
                    <?php if (empty($data['cover_img'])): ?><div class="ok-icon-circle mb-4"><i class="bi bi-patch-question-fill"></i></div><?php endif; ?>
                    <h2 class="fw-bold mb-3"><?php echo $data['title']; ?></h2>
                    <p class="text-muted mb-4">კითხვები: <strong><?php echo count($data['questions']); ?></strong></p>
                    <button class="btn btn-primary btn-lg rounded-pill px-5 ok-btn-start">დაწყება</button>
                </div>
            </div>
        </div>
        <div class="ok-quiz-question ok-step" style="display:none;"><div class="ok-quiz-card"><div class="ok-img-header ok-q-img-wrap" style="display:none"><img src="" class="ok-full-img ok-q-img-el"></div><div class="ok-card-body"><div class="d-flex justify-content-between mb-3"><span class="badge bg-light text-primary border rounded-pill px-3"><span class="ok-cur">1</span> / <span class="ok-tot">5</span></span></div><div class="progress mb-4" style="height:6px"><div class="progress-bar bg-primary rounded-pill" style="width:0%"></div></div><h4 class="fw-bold mb-4 ok-q-text"></h4><div class="ok-opts"></div></div></div></div>
        <div class="ok-quiz-result ok-step" style="display:none;">
            <div class="ok-quiz-card text-center"><div class="ok-card-body">
                <div class="ok-res-icon mb-3" style="font-size:4rem">🏆</div>
                <h2 class="fw-bold">შედეგი</h2>
                <div class="display-1 fw-bold text-primary mb-3"><span class="ok-score">0</span>/<span class="ok-tot-s">0</span></div>
                <p class="ok-msg lead mb-4"></p>
                <div class="ok-share-area mb-4 pt-3 border-top"><div class="d-flex justify-content-center gap-2"><button class="btn btn-outline-primary ok-share-fb"><i class="bi bi-facebook"></i> Facebook</button><button class="btn btn-outline-info ok-share-tg"><i class="bi bi-telegram"></i> Telegram</button></div></div>
                <button class="btn btn-primary rounded-pill px-4 ok-btn-restart">თავიდან</button>
            </div></div>
        </div>
    </div>
    <style>
        .ok-quiz-wrapper { max-width: 600px; margin: 20px auto; font-family: 'Noto Sans Georgian', sans-serif; }
        .ok-quiz-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.04); overflow: hidden; }
        .ok-full-img { width: 100%; display: block; border-radius: 16px 16px 0 0; object-fit: cover; max-height: 350px; }
        .ok-card-body { padding: 2rem; }
        .ok-opts { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .ok-option-btn { text-align: center; padding: 15px; border: 2px solid #f1f5f9; background: #fff; border-radius: 12px; cursor: pointer; transition: .2s; min-height: 60px; display: flex; align-items: center; justify-content: center; }
        .ok-option-btn:hover { background: #f8fafc; transform: translateY(-2px); border-color: #dee2e6; }
        .ok-option-btn.correct { border-color: #198754!important; background: #d1e7dd!important; color: #0f5132!important; }
        .ok-option-btn.wrong { border-color: #dc3545!important; background: #f8d7da!important; color: #842029!important; }
    </style>
    <script>
    (function(){
        let w=document.getElementById('<?php echo $uid; ?>'); if(!w)return;
        let d=JSON.parse(w.getAttribute('data-quiz')), qs=d.questions, cur=0, sc=0, ans=false;
        let els={ s:w.querySelector('.ok-quiz-start'), q:w.querySelector('.ok-quiz-question'), r:w.querySelector('.ok-quiz-result'), qt:w.querySelector('.ok-q-text'), ls:w.querySelector('.ok-opts'), pr:w.querySelector('.progress-bar'), cn:w.querySelector('.ok-cur'), tn:w.querySelector('.ok-tot'), qImgWrap:w.querySelector('.ok-q-img-wrap'), qImg:w.querySelector('.ok-q-img-el'), resMsg:w.querySelector('.ok-msg'), btnFb:w.querySelector('.ok-share-fb'), btnTg:w.querySelector('.ok-share-tg') };
        w.querySelector('.ok-btn-start').onclick=()=>{els.s.style.display='none';els.q.style.display='block';load();};
        w.querySelector('.ok-btn-restart').onclick=()=>{cur=0;sc=0;els.r.style.display='none';els.s.style.display='block';};
        function load(){ ans=false; let q=qs[cur]; els.qt.textContent=q.q; els.cn.textContent=cur+1; els.tn.textContent=qs.length; els.pr.style.width=((cur)/qs.length)*100+'%'; if(q.img){ els.qImg.src=q.img; els.qImgWrap.style.display='block'; } else { els.qImgWrap.style.display='none'; } els.ls.innerHTML=''; q.options.forEach((o,i)=>{ let b=document.createElement('div'); b.className='ok-option-btn'; b.textContent=o; b.onclick=()=>{if(ans)return; ans=true; if(i===q.correct){b.classList.add('correct');sc++;}else{b.classList.add('wrong');els.ls.children[q.correct].classList.add('correct');} setTimeout(()=>{cur++; if(cur<qs.length)load(); else res();},1200);}; els.ls.appendChild(b); }); }
        function res(){
            els.q.style.display='none'; els.r.style.display='block'; w.querySelector('.ok-score').textContent=sc; w.querySelector('.ok-tot-s').textContent=qs.length;
            let ft = ""; if(d.results) d.results.forEach(r => { if(sc >= parseInt(r.min) && sc <= parseInt(r.max)) ft = r.msg; }); els.resMsg.textContent = ft;
            let sh = d.share_config || {};
            let p = new URLSearchParams({ ok_chk_share: '1', s: sc, t: qs.length, title: d.title, msg: ft, w: sh.w||1200, h: sh.h||630, bgv: (sh.bg_val||'ffffff').replace('#',''), bgt: sh.bg_type||'col', ct: sh.c_txt||'', c_sh: sh.c_sh??0, c_x: sh.c_x||'', c_y: sh.c_y||50, c_sz: sh.c_sz||20, c_c: (sh.c_c||'555555').replace('#',''), t_sh: sh.t_sh??1, t_x: sh.t_x||'', t_y: sh.t_y||120, t_sz: sh.t_sz||30, t_c: (sh.t_c||'000000').replace('#',''), s_sh: sh.s_sh??1, s_x: sh.s_x||'', s_y: sh.s_y||280, s_sz: sh.s_sz||80, s_c: (sh.s_c||'0d6efd').replace('#',''), m_sh: sh.m_sh??1, m_x: sh.m_x||'', m_y: sh.m_y||450, m_sz: sh.m_sz||25, m_c: (sh.m_c||'333333').replace('#','') });
            let shareUrl = window.location.href.split('?')[0] + '?' + p.toString();
            els.btnFb.onclick = () => window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`, '_blank', 'width=600,height=500');
            els.btnTg.onclick = () => window.open(`https://t.me/share/url?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(d.title)}`, '_blank');
        }
    })();
    </script>
    <?php return ob_get_clean();
}
if (function_exists('add_ok_shortcode')) { add_ok_shortcode('ok_quiz', 'ok_quiz_render'); }
if (function_exists('add_ok_action')) { 
    add_ok_action('admin_menu', function() { add_menu_page('ქვიზები','ქვიზები','manage_options','ok-quiz','ok_render_quiz_admin_page','bi bi-joystick',30); }); 
    add_ok_action('ok_head', function() { echo ok_quiz_get_meta_tags(); });
}