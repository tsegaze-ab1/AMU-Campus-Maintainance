<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/categories.php';

require_role(['admin']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $error = 'Category name is required.';
        } elseif (mb_strlen($name) > 100) {
            $error = 'Category name is too long.';
        } else {
            create_category($name);
            header('Location: ' . base_url('/admin/categories.php'));
            exit;
        }
    } elseif ($action === 'update') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $name = trim((string)($_POST['name'] ?? ''));

        if (!$id) {
            $error = 'Invalid category.';
        } elseif ($name === '') {
            $error = 'Category name is required.';
        } elseif (mb_strlen($name) > 100) {
            $error = 'Category name is too long.';
        } else {
            update_category((int)$id, $name);
            header('Location: ' . base_url('/admin/categories.php'));
            exit;
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            $error = 'Invalid category.';
        } else {
            delete_category((int)$id);
            header('Location: ' . base_url('/admin/categories.php'));
            exit;
        }
    } else {
        $error = 'Invalid action.';
    }
}

$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editItem = $editId ? find_category((int)$editId) : null;

$categories = list_categories();

render_header('Admin - Categories');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0">Categories</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo h($error); ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3"><?php echo $editItem ? 'Edit Category' : 'Add Category'; ?></h2>

                <form method="post" action="<?php echo h(base_url('/admin/categories.php')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $editItem ? 'update' : 'create'; ?>" />
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$editItem['id']; ?>" />
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label" for="cat_name">Name</label>
                        <input id="cat_name" type="text" name="name" class="form-control" required value="<?php echo h($editItem['name'] ?? ''); ?>" />
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit"><?php echo $editItem ? 'Save' : 'Add'; ?></button>
                        <?php if ($editItem): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo h(base_url('/admin/categories.php')); ?>">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (!$categories): ?>
                    <p class="mb-0">No categories yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td><?php echo (int)$c['id']; ?></td>
                                        <td><?php echo h($c['name']); ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo h(base_url('/admin/categories.php?edit=' . (int)$c['id'])); ?>">Edit</a>

                                            <form method="post" action="<?php echo h(base_url('/admin/categories.php')); ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete" />
                                                <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>" />
                                                <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this category?');">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
render_footer();
