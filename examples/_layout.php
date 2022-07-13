<?php
use Kekos\PrestDoc\AssetsRepository;

/**
 * @var AssetsRepository $assets
 * @var array|null $front_matter
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="en">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (isset($front_matter['title'])): ?>
    <title>
        <?php echo htmlspecialchars($front_matter['title']); ?>
    </title>
<?php endif; ?>
<?php foreach ($assets->getCss() as $css_path): ?>
    <link rel="stylesheet" href="<?php echo $css_path; ?>" />
<?php endforeach; ?>
    <link rel="stylesheet" href="static/screen.css" />
    <script src="static/api_doc.js" defer></script>
<?php foreach ($assets->getJavaScript() as $js_path): ?>
    <script src="<?php echo $js_path; ?>" defer></script>
<?php endforeach; ?>

<main class="prest-doc-main-wrapper">
<?php echo $content; ?>
</main>

</html>
