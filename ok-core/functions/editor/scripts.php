<script>
// --- გლობალური ფუნქციები ---
if (typeof window.okFormat === 'undefined') {
    window.okFormat = function(cmd, val = null) {
        document.execCommand(cmd, false, val);
    };

    window.okAddLink = function() {
        const url = prompt('შეიყვანეთ ბმული (URL):');
        if (url) window.okFormat('createLink', url);
    };

    // YouTube ვიდეოს ჩასმა
    window.okInsertVideo = function(editorId) {
        const url = prompt('ჩაწერეთ YouTube ვიდეოს ბმული:');
        if (!url) return;
        
        // მარტივი პარსინგი ID-ს ამოსაღებად
        let videoId = url.split('v=')[1];
        const ampersandPosition = videoId ? videoId.indexOf('&') : -1;
        if(ampersandPosition != -1) {
            videoId = videoId.substring(0, ampersandPosition);
        }
        
        if (videoId) {
            const embedHtml = `<div class="ratio ratio-16x9 my-3"><iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe></div><p><br></p>`;
            window.okInsertHtml(editorId, embedHtml);
        } else {
            alert('არასწორი YouTube ბმული');
        }
    };

    // ცხრილის ჩასმა
    window.okInsertTable = function(editorId) {
        const rows = prompt('რამდენი რიგი? (Rows)', 3);
        const cols = prompt('რამდენი სვეტი? (Cols)', 3);
        if(rows && cols) {
            let html = '<table class="table table-bordered"><tbody>';
            for(let i=0; i<rows; i++) {
                html += '<tr>';
                for(let j=0; j<cols; j++) {
                    html += '<td>ტექსტი</td>';
                }
                html += '</tr>';
            }
            html += '</tbody></table><p><br></p>';
            window.okInsertHtml(editorId, html);
        }
    };

    // სპეც სიმბოლოები (მარტივი ვერსია)
    window.okInsertChar = function(editorId) {
        const char = prompt('ჩაწერეთ სიმბოლო (მაგ: ©, ®, ™, €, §):');
        if(char) window.okInsertHtml(editorId, char);
    };

    // HTML-ის ჩასმის დამხმარე ფუნქცია
    window.okInsertHtml = function(editorId, html) {
        const editor = document.getElementById(editorId);
        if(editor) {
            editor.focus();
            document.execCommand('insertHTML', false, html);
            // სინქრონიზაცია
            document.getElementById('hidden_' + editorId).value = editor.innerHTML;
            document.getElementById('source_' + editorId).value = editor.innerHTML;
        }
    };
    
    // სურათის ჩასმა გარე მოდულიდან
    window.okInsertImage = function(editorId, url) {
        window.okInsertHtml(editorId, `<img src="${url}">`);
    };
}

// --- კონკრეტული ედიტორის ლოგიკა ---
(function() {
    const editorId = '<?php echo $id; ?>';
    const wrapper = document.getElementById('toolbar_' + editorId).parentNode; // ok-editor-wrapper
    const editor = document.getElementById(editorId);
    const hiddenInput = document.getElementById('hidden_' + editorId);
    const sourceInput = document.getElementById('source_' + editorId);
    
    // სურათის ხელსაწყოები
    const toolsContainer = document.getElementById('img-tools-' + editorId);
    const widthInput = document.getElementById('img-w-' + editorId);
    let selectedImage = null;

    // --- Source Code Toggle ---
    window.okToggleSource = function(id) {
        if (id !== editorId) return;
        
        if (wrapper.classList.contains('source-mode')) {
            // HTML -> Visual
            wrapper.classList.remove('source-mode');
            editor.innerHTML = sourceInput.value;
            hiddenInput.value = sourceInput.value;
        } else {
            // Visual -> HTML
            wrapper.classList.add('source-mode');
            sourceInput.value = editor.innerHTML;
        }
    };
    
    // თუ Source-ში ვწერთ, განახლდეს hiddenInput
    sourceInput.addEventListener('input', () => {
        hiddenInput.value = sourceInput.value;
    });

    // --- Fullscreen Toggle ---
    window.okToggleFullscreen = function(id) {
        if (id !== editorId) return;
        wrapper.classList.toggle('ok-fullscreen');
    };

    // --- Image Logic ---
    editor.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG') {
            editor.querySelectorAll('img').forEach(img => img.classList.remove('selected'));
            selectedImage = e.target;
            selectedImage.classList.add('selected');
            widthInput.value = selectedImage.clientWidth;
            toolsContainer.style.display = 'flex';
        } else {
            if (selectedImage) {
                selectedImage.classList.remove('selected');
                selectedImage = null;
                toolsContainer.style.display = 'none';
            }
        }
    });

    window.okApplyImgSize = function(id) {
        if (id !== editorId || !selectedImage) return;
        selectedImage.style.width = widthInput.value + 'px';
        selectedImage.style.height = 'auto';
        syncContent();
    };

    window.okImgAlign = function(id, type) {
        if (id !== editorId || !selectedImage) return;
        selectedImage.style.float = 'none';
        selectedImage.style.display = 'inline-block';
        selectedImage.style.margin = '5px';
        if (type === 'left') { selectedImage.style.float = 'left'; selectedImage.style.margin = '0 15px 15px 0'; }
        else if (type === 'right') { selectedImage.style.float = 'right'; selectedImage.style.margin = '0 0 15px 15px'; }
        else if (type === 'center') { selectedImage.style.display = 'block'; selectedImage.style.margin = '15px auto'; }
        syncContent();
    };

    window.okDelImg = function(id) {
        if (id !== editorId || !selectedImage) return;
        selectedImage.remove();
        selectedImage = null;
        toolsContainer.style.display = 'none';
        syncContent();
    };

    // --- Sync Logic ---
    function syncContent() {
        if (selectedImage) selectedImage.classList.remove('selected');
        const html = editor.innerHTML;
        hiddenInput.value = html;
        sourceInput.value = html; // სორსსაც ვააფდეითებთ
        if (selectedImage) selectedImage.classList.add('selected');
    }

    editor.addEventListener('input', syncContent);
    
    // ფორმის გაგზავნისას
    const parentForm = editor.closest('form');
    if (parentForm) {
        parentForm.addEventListener('submit', () => {
            // თუ სორს მოდშია, იქიდან ავიღოთ ბოლო ინფო
            if (wrapper.classList.contains('source-mode')) {
                hiddenInput.value = sourceInput.value;
            } else {
                if (selectedImage) selectedImage.classList.remove('selected');
                hiddenInput.value = editor.innerHTML;
            }
        });
    }
})();
</script>