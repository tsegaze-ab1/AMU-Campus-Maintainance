<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/requests.php';
require_once __DIR__ . '/../includes/categories.php';

require_role(['student']);

$user = current_user();
$studentId = (int)($user['id'] ?? 0);

$error = '';
$title = '';
$description = '';
$location = '';
$priority = 'medium';
$categoryId = null;

$categories = list_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();

    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $priority = trim((string)($_POST['priority'] ?? 'medium'));

    $categoryRaw = $_POST['category_id'] ?? '';
    if ($categoryRaw === '' || $categoryRaw === null) {
        $categoryId = null;
    } else {
        $categoryId = filter_var($categoryRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    if ($title === '' || $description === '' || $location === '') {
        $error = 'Please fill in title, description, and location.';
    } elseif (mb_strlen($title) < 5 || mb_strlen($title) > 150) {
        $error = 'Title must be between 5 and 150 characters.';
    } elseif (mb_strlen($description) < 10) {
        $error = 'Description must be at least 10 characters.';
    } elseif (!is_valid_priority($priority)) {
        $error = 'Invalid priority.';
    } else {
        $newId = create_request($studentId, $categoryId, $title, $description, $location, $priority);
        header('Location: ' . base_url('/student/view.php?id=' . $newId));
        exit;
    }
}

render_header('Create Request');
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 m-0">Create Maintenance Request</h1>
            <a class="btn btn-outline-secondary" href="<?php echo h(base_url('/student/index.php')); ?>">Back</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" action="<?php echo h(base_url('/student/create.php')); ?>">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" <?php echo ($categoryId !== null && (int)$categoryId === (int)$c['id']) ? 'selected' : ''; ?>>
                                    <?php echo h($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required value="<?php echo h($title); ?>" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" required value="<?php echo h($location); ?>" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select" required>
                            <?php foreach (request_allowed_priorities() as $p): ?>
                                <option value="<?php echo h($p); ?>" <?php echo ($priority === $p) ? 'selected' : ''; ?>><?php echo h($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="5" required><?php echo h($description); ?></textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
render_footer();
