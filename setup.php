<?php
/**
 * CMS Setup Script
 * 
 * Initializes the Dalthaus.net CMS with database, directories, and sample content.
 * Run this script once after installation to set up the system.
 * Safe to run multiple times - uses IF NOT EXISTS clauses.
 * 
 * Setup Process:
 * 1. Creates database and tables
 * 2. Creates required directories
 * 3. Tests logging functionality
 * 4. Adds sample content
 * 5. Displays setup summary
 * 
 * Usage:
 *   php setup.php
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */
declare(strict_types=1);
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Display setup header
echo "Dalthaus.net CMS Setup\n";
echo "=====================\n\n";

try {
    /**
     * Step 1: Database Setup
     * Creates database if missing and initializes all tables
     */
    echo "1. Setting up database...\n";
    Database::setup();
    echo "   ✓ Database and tables created\n\n";
    
    /**
     * Step 2: Directory Creation
     * Creates all required directories with proper permissions
     */
    echo "2. Creating directories...\n";
    $dirs = [
        UPLOAD_PATH,  // User uploads directory
        CACHE_PATH,   // Page cache directory
        LOG_PATH,     // Application logs directory
        TEMP_PATH     // Temporary files directory
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            // Create directory with 755 permissions
            mkdir($dir, 0755, true);
            echo "   ✓ Created: $dir\n";
        } else {
            echo "   ✓ Exists: $dir\n";
        }
    }
    echo "\n";
    
    /**
     * Step 3: Logging Test
     * Verifies logging system is working
     */
    echo "3. Testing logging...\n";
    logMessage('Setup completed successfully', 'info');
    echo "   ✓ Log file created\n\n";
    
    /**
     * Step 4: Sample Content Creation
     * Adds example article and photobook to demonstrate features
     */
    echo "4. Adding sample content...\n";
    
    // Get database connection
    $pdo = Database::getInstance();
    $dbName = TEST_MODE ? TEST_DATABASE : DB_NAME;
    $pdo->exec("USE `$dbName`");
    
    /**
     * Create Sample Article
     * Simple welcome article to demonstrate article functionality
     */
    $stmt = $pdo->prepare("
        INSERT INTO content (type, title, slug, body, status) 
        VALUES ('article', 'Welcome to Dalthaus.net', 'welcome', 
                '<p>Welcome to your new CMS!</p><p>This is a sample article to get you started.</p>', 
                'published')
    ");
    $stmt->execute();
    echo "   ✓ Sample article created\n";
    
    /**
     * Create Sample Photobook Story
     * Multi-page story demonstrating photobook features including:
     * - Multiple pages with <!-- page --> breaks
     * - Image placeholders with different alignments
     * - Rich narrative content
     */
    $storyContent = '<p>Once upon a time, in a small village nestled between rolling hills and ancient forests, there lived a young photographer named Elena. She had inherited an old camera from her grandmother, along with a collection of mysterious photographs that seemed to tell a story of their own.</p>

<p>Elena discovered that each photograph held a secret—a moment frozen in time that revealed something extraordinary about her family\'s past. The first image showed her grandmother as a young woman, standing before a grand oak tree that no longer existed in the village.</p>

<img src="/assets/sample-story1.jpg" alt="The ancient oak tree" class="img-center">

<p>As Elena delved deeper into the collection, she began to understand that her grandmother had been documenting something remarkable. Each photograph was a piece of a larger puzzle, a narrative that spanned decades and connected generations.</p>

<!-- page -->

<p>The second photograph led Elena to the old village library, where dusty records revealed that the oak tree had been a meeting place for the village storytellers. Her grandmother had been the last keeper of these oral traditions, preserving them not just in words, but in carefully composed images.</p>

<img src="/assets/sample-story2.jpg" alt="The village library" class="img-left">

<p>Page by page, photograph by photograph, Elena reconstructed the stories. She learned about the harvest festivals, the winter gatherings, and the spring celebrations that had defined her community for centuries. Each image was accompanied by her grandmother\'s meticulous notes, written in flowing script on the backs of the photographs.</p>

<p>The most striking discovery was a series of portraits—faces of villagers from different eras, all sharing the same gentle smile and determined eyes. These were the storytellers, the keepers of memory, and Elena realized she was meant to continue their legacy.</p>

<!-- page -->

<p>With her grandmother\'s camera in hand, Elena began her own journey. She photographed the village as it existed now, capturing the stories of the current generation. She interviewed the elders, recorded their voices, and paired their words with her images.</p>

<p>The project grew into something beautiful—a living archive that bridged past and present. Elena\'s photobook became a treasure for the village, displayed in the library where her grandmother\'s photographs had first inspired her.</p>

<img src="/assets/sample-story3.jpg" alt="Elena\'s exhibition" class="img-full">

<p>And so, the tradition continued. Through the lens of a camera and the pages of a book, the stories lived on, connecting one generation to the next in an unbroken chain of memory and love.</p>';
    
    // Insert photobook story into database
    $stmt = $pdo->prepare("
        INSERT INTO content (type, title, slug, body, status) 
        VALUES ('photobook', 'The Storyteller\'s Legacy', 'storytellers-legacy', ?, 'published')
    ");
    $stmt->execute([$storyContent]);
    echo "   ✓ Sample photobook story created\n\n";
    
    /**
     * Step 5: Display Setup Summary
     * Shows configuration details and access information
     */
    echo "5. Setup Summary:\n";
    echo "   ✓ Database: " . DB_NAME . "\n";
    echo "   ✓ Environment: " . ENV . "\n";
    echo "   ✓ Admin User: " . DEFAULT_ADMIN_USER . "\n";
    echo "   ✓ Admin Pass: " . DEFAULT_ADMIN_PASS . "\n\n";
    
    // Display success message with access instructions
    echo "SETUP COMPLETE!\n\n";
    echo "You can now access:\n";
    echo "- Admin Panel: http://localhost:8000/admin\n";
    echo "- Public Site: http://localhost:8000/\n\n";
    echo "To start the development server, run:\n";
    echo "php -S localhost:8000\n\n";
    
} catch (Exception $e) {
    /**
     * Error Handling
     * Display error message and configuration hint
     */
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Please check your configuration in includes/config.php\n";
    exit(1);
}