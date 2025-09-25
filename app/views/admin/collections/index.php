<?php use function App\Core\csrf_field; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4">Collections</h1>
  <a class="btn btn-dark" href="/admin/collections/create">Create Collection</a>
</div>
<table class="table">
  <thead><tr><th>ID</th><th>Title</th><th>Slug</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($collections as $c): ?>
    <tr>
      <td><?= (int)$c['id'] ?></td>
      <td><?= htmlspecialchars($c['title']) ?></td>
      <td><?= htmlspecialchars($c['slug']) ?></td>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-secondary" href="/admin/collections/<?= (int)$c['id'] ?>/edit">Edit</a>
        <form class="d-inline" method="post" action="/admin/collections/<?= (int)$c['id'] ?>/delete">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete collection?')">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

