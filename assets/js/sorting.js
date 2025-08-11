/**
 * Drag-and-Drop Sorting Module
 * 
 * Provides drag-and-drop reordering functionality for menus and content lists.
 * Integrates with Sortable.js library for smooth drag interactions.
 * Automatically saves new order to server via AJAX on drop.
 * 
 * Features:
 * - Drag handle for controlled dragging
 * - Visual ghost element during drag
 * - Automatic AJAX save on reorder
 * - Success/error notifications
 * - CSRF token validation
 * 
 * Dependencies:
 * - Sortable.js library
 * - Server endpoint at /admin/api/sort
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */
(function() {
    /**
     * Initialize all sortable lists on the page
     * 
     * Finds elements with .sortable-list class and makes them sortable.
     * Sets up drag handles, animation, and save callback.
     */
    const sortableLists = document.querySelectorAll('.sortable-list');
    
    sortableLists.forEach(list => {
        // Create new Sortable instance for each list
        new Sortable(list, {
            handle: '.sortable-handle',     // Drag handle selector
            animation: 150,                  // Animation duration in ms
            ghostClass: 'sortable-ghost',    // Class for ghost element
            onEnd: function(evt) {           // Callback after drop
                saveOrder(list);
            }
        });
    });
    
    /**
     * Save reordered items to server
     * 
     * Collects new order of items and sends to server via AJAX.
     * Includes CSRF token for security validation.
     * Shows success/error notification based on response.
     * 
     * @param {HTMLElement} list The sortable list element
     * @return {void}
     */
    function saveOrder(list) {
        // Get all sortable items in new order
        const items = list.querySelectorAll('.sortable-item');
        const location = list.dataset.location;  // Menu location (top/bottom)
        const order = [];                        // Array to hold new order
        
        // Build order array with IDs and positions
        items.forEach((item, index) => {
            order.push({
                id: item.dataset.id,      // Item ID from data attribute
                order: index + 1          // 1-based position
            });
        });
        
        // Get CSRF token for security
        const csrfToken = document.querySelector('[name="csrf_token"]').value;
        
        // Send new order to server
        fetch('/admin/api/sort', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: csrfToken,
                location: location,       // Which menu/list
                order: order              // New item order
            })
        })
        .then(response => response.json())
        .then(data => {
            // Show appropriate notification
            if (data.success) {
                showNotification('Order saved');
            } else {
                showNotification('Error saving order', 'error');
            }
        })
        .catch(error => {
            // Log and show error notification
            console.error('Error:', error);
            showNotification('Error saving order', 'error');
        });
    }
    
    /**
     * Display temporary notification message
     * 
     * Creates and shows a notification popup with slide animation.
     * Auto-dismisses after 3 seconds with slide-out animation.
     * Supports success and error styling.
     * 
     * @param {string} message Text to display in notification
     * @param {string} type Notification type ('success' or 'error')
     * @return {void}
     */
    function showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Apply inline styles for positioning and appearance
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
            color: white;
            border-radius: 5px;
            animation: slideIn 0.3s ease;
            z-index: 1000;
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            // Trigger slide-out animation
            notification.style.animation = 'slideOut 0.3s ease';
            
            // Remove from DOM after animation completes
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    /**
     * Add CSS animations for notifications
     * 
     * Injects keyframe animations for slide in/out effects.
     * Applied dynamically to avoid external CSS dependency.
     */
    const style = document.createElement('style');
    style.textContent = `
        /* Slide in from right animation */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Slide out to right animation */
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
})();