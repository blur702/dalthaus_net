/**
 * Autosave Functionality Module
 * 
 * Provides automatic content saving every 30 seconds for CMS editors.
 * Integrates with TinyMCE editor and sends content to server via AJAX.
 * Prevents data loss by saving before page unload if content changed.
 * 
 * Features:
 * - 30-second interval autosave
 * - Change detection to avoid unnecessary saves
 * - Visual feedback with autosave indicator
 * - BeforeUnload save for unsaved changes
 * - CSRF token validation
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */
(function() {
    /**
     * Timer reference for autosave interval
     * @type {number|null}
     */
    let autosaveTimer = null;
    
    /**
     * Last saved content for change detection
     * @type {string}
     */
    let lastSavedContent = '';
    
    /**
     * Initialize autosave functionality
     * 
     * Sets up autosave timer and beforeunload handler.
     * Only activates if editor form and content ID are present.
     * 
     * @return {void}
     */
    function initAutosave() {
        // Find the editor form element
        const form = document.getElementById('editor-form');
        if (!form) return;
        
        // Get content and ID fields
        const contentField = form.querySelector('#body');
        const idField = form.querySelector('[name="id"]');
        
        // Ensure required fields exist and content has an ID
        if (!contentField || !idField || !idField.value) return;
        
        // Start autosave timer - triggers every 30 seconds
        autosaveTimer = setInterval(() => {
            autosave(idField.value);
        }, 30000); // 30 seconds
        
        // Save on page unload if content has changed
        window.addEventListener('beforeunload', () => {
            // Get current content from TinyMCE or fallback to textarea
            const currentContent = tinymce.get('body')?.getContent() || contentField.value;
            
            // Only save if content has changed
            if (currentContent !== lastSavedContent) {
                autosave(idField.value);
            }
        });
    }
    
    /**
     * Perform autosave operation
     * 
     * Sends current content to server via AJAX POST.
     * Includes CSRF token for security validation.
     * Shows visual feedback on success.
     * 
     * @param {string} contentId ID of content being saved
     * @return {void}
     */
    function autosave(contentId) {
        // Get form fields
        const titleField = document.getElementById('title');
        const bodyField = document.getElementById('body');
        const csrfField = document.querySelector('[name="csrf_token"]');
        
        // Validate required fields exist
        if (!titleField || !bodyField || !csrfField) return;
        
        // Get content from TinyMCE if available, otherwise from textarea
        const content = tinymce.get('body')?.getContent() || bodyField.value;
        
        // Skip save if content hasn't changed
        if (content === lastSavedContent) return;
        
        // Prepare form data for submission
        const data = new FormData();
        data.append('content_id', contentId);
        data.append('title', titleField.value);
        data.append('body', content);
        data.append('csrf_token', csrfField.value);
        
        // Send autosave request to server
        fetch('/admin/api/autosave', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Update last saved content for change detection
                lastSavedContent = content;
                // Show success indicator
                showAutosaveIndicator('Autosaved');
            }
        })
        .catch(error => {
            // Log errors but don't interrupt user
            console.error('Autosave failed:', error);
        });
    }
    
    /**
     * Display autosave status indicator
     * 
     * Shows temporary notification when content is autosaved.
     * Creates indicator element if it doesn't exist.
     * Auto-hides after 3 seconds.
     * 
     * @param {string} message Message to display in indicator
     * @return {void}
     */
    function showAutosaveIndicator(message) {
        // Find or create indicator element
        let indicator = document.getElementById('autosave-indicator');
        if (!indicator) {
            // Create new indicator element
            indicator = document.createElement('div');
            indicator.id = 'autosave-indicator';
            indicator.className = 'autosave-indicator';
            document.body.appendChild(indicator);
        }
        
        // Set message and show indicator
        indicator.textContent = message;
        indicator.classList.add('show');
        
        // Hide indicator after 3 seconds
        setTimeout(() => {
            indicator.classList.remove('show');
        }, 3000);
    }
    
    // Initialize when DOM is ready
    // Handles both loading and already loaded states
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutosave);
    } else {
        initAutosave();
    }
})();