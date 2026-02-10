<style>
    /* --- ძირითადი კონტეინერი --- */
    .ok-editor-wrapper { 
        border: 1px solid #dee2e6; 
        border-radius: 6px; 
        background: #fff; 
        display: flex; 
        flex-direction: column;
        overflow: hidden; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.02); 
        margin-bottom: 15px;
        position: relative;
        transition: all 0.3s ease;
    }

    /* --- სრული ეკრანის რეჟიმი --- */
    .ok-editor-wrapper.ok-fullscreen {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 9999;
        border-radius: 0;
        height: 100vh !important;
        margin: 0;
    }
    .ok-editor-wrapper.ok-fullscreen .ok-editor-content,
    .ok-editor-wrapper.ok-fullscreen .ok-source-textarea {
        height: calc(100vh - 50px) !important;
    }

    /* --- თულბარი --- */
    .ok-editor-toolbar { 
        background: #f8f9fa; 
        padding: 6px; 
        border-bottom: 1px solid #dee2e6; 
        display: flex; 
        align-items: center; 
        gap: 4px; 
        flex-wrap: wrap; 
    }

    /* --- ღილაკები --- */
    .ok-editor-btn, .ok-editor-select { 
        background: white; 
        border: 1px solid #ced4da; 
        padding: 5px 8px; 
        cursor: pointer; 
        border-radius: 4px; 
        color: #495057; 
        transition: all 0.2s; 
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 30px;
    }
    .ok-editor-btn:hover { 
        background: #e9ecef; 
        color: #000; 
        border-color: #adb5bd;
    }
    .ok-editor-select {
        width: auto;
        padding-right: 20px;
        font-size: 13px;
    }

    /* --- ფერების ინპუტი --- */
    .ok-color-picker {
        border: 1px solid #ced4da;
        width: 30px;
        height: 30px;
        padding: 2px;
        cursor: pointer;
        border-radius: 4px;
        background: #fff;
    }

    /* --- მთავარი ველი (ვიზუალური) --- */
    .ok-editor-content { 
        height: 500px; 
        padding: 20px; 
        outline: none; 
        overflow-y: auto; 
        font-size: 16px; 
        line-height: 1.6; 
        color: #333; 
        background: #fff;
    }
    
    /* --- HTML Source Code ველი (თავიდან დამალული) --- */
    .ok-source-textarea {
        display: none;
        width: 100%;
        height: 500px;
        padding: 20px;
        font-family: monospace;
        background: #2d2d2d;
        color: #ddd;
        border: none;
        outline: none;
        resize: none;
        font-size: 14px;
    }
    .ok-editor-wrapper.source-mode .ok-editor-content { display: none; }
    .ok-editor-wrapper.source-mode .ok-source-textarea { display: block; }
    .ok-editor-wrapper.source-mode .ok-editor-btn:not(.source-toggle-btn) { opacity: 0.3; pointer-events: none; }

    /* --- ელემენტების სტილები შიგნით --- */
    .ok-editor-content p { margin-bottom: 1em; }
    .ok-editor-content ul, .ok-editor-content ol { margin-left: 20px; margin-bottom: 1em; }
    
    .ok-editor-content blockquote { 
        border-left: 4px solid #0d6efd; 
        margin: 1em 0; 
        padding: 10px 15px; 
        color: #555; 
        background: #f8f9fa;
        font-style: italic;
    }
    
    .ok-editor-content pre {
        background: #f4f4f4;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #ddd;
        font-family: monospace;
        overflow-x: auto;
    }

    .ok-editor-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1em;
    }
    .ok-editor-content table td, .ok-editor-content table th {
        border: 1px solid #ddd;
        padding: 8px;
    }
    .ok-editor-content table th { background: #f8f9fa; font-weight: bold; }

    .ok-editor-content img { 
        max-width: 100%; 
        height: auto; 
        display: inline-block; 
        margin: 5px; 
        cursor: pointer; 
        transition: outline 0.1s; 
    }
    .ok-editor-content img.selected { outline: 3px solid #0d6efd; }
    
    /* --- გამყოფი ხაზი (Divider) --- */
    .vr { border-left: 1px solid #ccc; height: 20px; margin: 0 5px; }

    /* --- სურათის მენიუ --- */
    .ok-img-tools { 
        display: none; align-items: center; gap: 5px; margin-left: 10px; 
        padding-left: 10px; border-left: 2px solid #ccc; 
    }
    .ok-img-inp { width: 60px; padding: 4px; font-size: 13px; border: 1px solid #ccc; border-radius: 3px; }
</style>