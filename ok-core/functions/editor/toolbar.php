<div class="ok-editor-toolbar" id="toolbar_<?php echo $id; ?>">
    <button type="button" class="ok-editor-btn" onclick="okFormat('undo')" title="უკან დაბრუნება (Undo)"><i class="bi bi-arrow-counterclockwise"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('redo')" title="წინ წასვლა (Redo)"><i class="bi bi-arrow-clockwise"></i></button>
    
    <div class="vr"></div>

    <button type="button" class="ok-editor-btn" onclick="okFormat('bold')" title="გამუქება (Bold)"><i class="bi bi-type-bold"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('italic')" title="დახრა (Italic)"><i class="bi bi-type-italic"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('underline')" title="ხაზგასმა (Underline)"><i class="bi bi-type-underline"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('strikeThrough')" title="გადახაზვა (Strike)"><i class="bi bi-type-strikethrough"></i></button>
    
    <button type="button" class="ok-editor-btn" onclick="okFormat('superscript')" title="ხარისხში აყვანა (Superscript)">x²</button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('subscript')" title="ინდექსი (Subscript)">x₂</button>

    <div class="vr"></div>
    
    <select class="ok-editor-select" onchange="okFormat('fontSize', this.value)" title="შრიფტის ზომა">
        <option value="3">ზომა</option>
        <option value="1">ძალიან პატარა</option>
        <option value="2">პატარა</option>
        <option value="3">ჩვეულებრივი</option>
        <option value="4">საშუალო</option>
        <option value="5">დიდი</option>
        <option value="6">ძალიან დიდი</option>
        <option value="7">უზარმაზარი</option>
    </select>

    <select class="ok-editor-select" onchange="okFormat('fontName', this.value)" title="შრიფტის სტილი">
        <option value="Arial">შრიფტი</option>
        <option value="Arial">Arial</option>
        <option value="Georgia">Georgia</option>
        <option value="Verdana">Verdana</option>
        <option value="Courier New">Courier</option>
        <option value="Impact">Impact</option>
    </select>
    
    <div class="vr"></div>

    <button type="button" class="ok-editor-btn" onclick="okFormat('formatBlock', 'p')" title="პარაგრაფი"><i class="bi bi-paragraph"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('formatBlock', 'h2')" title="სათაური 2">H2</button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('formatBlock', 'h3')" title="სათაური 3">H3</button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('formatBlock', 'blockquote')" title="ციტატა"><i class="bi bi-quote"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('formatBlock', 'pre')" title="კოდის ბლოკი (Code)"><i class="bi bi-code-slash"></i></button>
    
    <div class="vr"></div>
    
    <input type="color" class="ok-color-picker" onchange="okFormat('foreColor', this.value)" title="ტექსტის ფერი">
    <input type="color" class="ok-color-picker" onchange="okFormat('hiliteColor', this.value)" value="#ffffff" title="ფონის ფერი (Highlight)">

    <div class="vr"></div>

    <button type="button" class="ok-editor-btn" onclick="okFormat('justifyLeft')" title="მარცხნივ"><i class="bi bi-text-left"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('justifyCenter')" title="ცენტრში"><i class="bi bi-text-center"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('justifyRight')" title="მარჯვნივ"><i class="bi bi-text-right"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('indent')" title="შეწევა (Indent)"><i class="bi bi-indent"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('outdent')" title="გამოწევა (Outdent)"><i class="bi bi-box-arrow-left"></i></button>

    <div class="vr"></div>

    <button type="button" class="ok-editor-btn" onclick="okFormat('insertUnorderedList')" title="ბულეტები"><i class="bi bi-list-ul"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('insertOrderedList')" title="ნუმერაცია"><i class="bi bi-list-ol"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okInsertTable('<?php echo $id; ?>')" title="ცხრილის ჩასმა"><i class="bi bi-table"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('insertHorizontalRule')" title="გამყოფი ხაზი"><i class="bi bi-hr"></i></button>

    <div class="vr"></div>
    
    <button type="button" class="ok-editor-btn" onclick="okAddLink()" title="ბმულის ჩასმა"><i class="bi bi-link"></i></button>
    <button type="button" class="ok-editor-btn" onclick="if(window.openGalleryForEditor) { window.openGalleryForEditor('<?php echo $id; ?>'); } else { alert('გალერეის მოდული არ არის ჩართული'); }" title="სურათის ჩასმა"><i class="bi bi-images"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okInsertVideo('<?php echo $id; ?>')" title="ვიდეოს ჩასმა (YouTube)"><i class="bi bi-youtube"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okInsertChar('<?php echo $id; ?>')" title="სპეც. სიმბოლოები">Ω</button>
    <button type="button" class="ok-editor-btn" onclick="okFormat('removeFormat')" title="ფორმატის მოშორება"><i class="bi bi-eraser"></i></button>

    <div class="vr"></div>

    <button type="button" class="ok-editor-btn source-toggle-btn" onclick="okToggleSource('<?php echo $id; ?>')" title="HTML კოდის ნახვა"><i class="bi bi-filetype-html"></i></button>
    <button type="button" class="ok-editor-btn" onclick="okToggleFullscreen('<?php echo $id; ?>')" title="მთელ ეკრანზე გაშლა"><i class="bi bi-arrows-fullscreen"></i></button>

    <div id="img-tools-<?php echo $id; ?>" class="ok-img-tools">
        <small class="text-muted">Px:</small>
        <input type="number" id="img-w-<?php echo $id; ?>" class="ok-img-inp">
        <button type="button" class="btn btn-sm btn-success p-0 px-2" onclick="okApplyImgSize('<?php echo $id; ?>')" title="ზომის შეცვლა"><i class="bi bi-check"></i></button>
        <div class="vr" style="height:15px"></div>
        <button type="button" class="btn btn-sm btn-light border p-0 px-2" onclick="okImgAlign('<?php echo $id; ?>', 'left')" title="მარცხნივ"><i class="bi bi-text-left"></i></button>
        <button type="button" class="btn btn-sm btn-light border p-0 px-2" onclick="okImgAlign('<?php echo $id; ?>', 'center')" title="ცენტრში"><i class="bi bi-arrows-expand"></i></button>
        <button type="button" class="btn btn-sm btn-light border p-0 px-2" onclick="okImgAlign('<?php echo $id; ?>', 'right')" title="მარჯვნივ"><i class="bi bi-text-right"></i></button>
        <button type="button" class="btn btn-sm btn-danger p-0 px-2" onclick="okDelImg('<?php echo $id; ?>')" title="წაშლა"><i class="bi bi-trash"></i></button>
    </div>
</div>

<textarea id="source_<?php echo $id; ?>" class="ok-source-textarea"><?php echo htmlspecialchars($content); ?></textarea>