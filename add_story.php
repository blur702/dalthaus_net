<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$pdo = Database::getInstance();
$pdo->exec('USE dalthaus_cms');

// First remove old gallery-style photobook if exists
$pdo->exec("DELETE FROM content WHERE type='photobook' AND (slug='sample-gallery' OR slug='storytellers-legacy')");

$storyContent = '<p>Once upon a time, in a small village nestled between rolling hills, there lived a young photographer named Elena. She had inherited an old camera from her grandmother, along with a collection of mysterious photographs that seemed to tell stories of their own.</p>

<p>Each photograph held a secret—a moment frozen in time that revealed something extraordinary about her family\'s past. The first image showed her grandmother as a young woman, standing before a grand oak tree that no longer existed in the village.</p>

<p>As Elena delved deeper into the collection, she began to understand that her grandmother had been documenting something remarkable. Each photograph was a piece of a larger puzzle, a narrative that spanned decades.</p>

<!-- page -->

<h2>Chapter 2: The Discovery</h2>

<p>The second photograph led Elena to the old village library, where dusty records revealed that the oak tree had been a meeting place for the village storytellers. Her grandmother had been the last keeper of these oral traditions, preserving them not just in words, but in carefully composed images.</p>

<p>Page by page, photograph by photograph, Elena reconstructed the stories. She learned about the harvest festivals, the winter gatherings, and the spring celebrations that had defined her community for centuries. Each image was accompanied by her grandmother\'s meticulous notes, written in flowing script on the backs of the photographs.</p>

<p>The most striking discovery was a series of portraits—faces of villagers from different eras, all sharing the same gentle smile and determined eyes. These were the storytellers, the keepers of memory, and Elena realized she was meant to continue their legacy.</p>

<!-- page -->

<h2>Chapter 3: The Journey Continues</h2>

<p>With her grandmother\'s camera in hand, Elena began her own journey. She photographed the village as it existed now, capturing the stories of the current generation. She interviewed the elders, recorded their voices, and paired their words with her images.</p>

<p>The project grew into something beautiful—a living archive that bridged past and present. Elena\'s photobook became a treasure for the village, displayed in the library where her grandmother\'s photographs had first inspired her.</p>

<p>And so, the tradition continued. Through the lens of a camera and the pages of a book, the stories lived on, connecting one generation to the next in an unbroken chain of memory and love.</p>';

$stmt = $pdo->prepare('INSERT INTO content (type, title, slug, body, status) VALUES (?, ?, ?, ?, ?)');
$stmt->execute(['photobook', 'The Storyteller\'s Legacy', 'storytellers-legacy', $storyContent, 'published']);

echo "✓ Story-based photobook 'The Storyteller's Legacy' has been created!\n";
echo "View it at: http://127.0.0.1:5500/photobook/storytellers-legacy\n";