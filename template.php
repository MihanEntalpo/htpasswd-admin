<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>htpasswd admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-3">
<div class="d-flex justify-content-between align-items-center mb-3">
<h1 class="mb-0">htpasswd admin</h1>
<button type="button" class="btn btn-info" id="showBackups">Резервные копии</button>
</div>
<form method="post" id="form">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
<table class="table" id="users">
<thead>
<tr>
<th class="text-center">&#10003;</th>
<th>Логин</th>
<th>Комментарий</th>
<th>Сбросить пароль</th>
<th>Задать пароль</th>
<th>Удалить</th>
</tr>
</thead>
<tbody>
<?php foreach ($entries as $i => $e): ?>
<tr class="<?= $e['active'] ? '' : 'table-secondary' ?>">
<td class="text-center"><input type="checkbox" name="active[<?= $i ?>]" <?= $e['active'] ? 'checked' : '' ?>></td>
<td><input type="text" class="form-control" name="username[<?= $i ?>]" value="<?= htmlspecialchars($e['username']) ?>"></td>
<td><textarea class="form-control" name="comment[<?= $i ?>]" rows="2"><?php echo htmlspecialchars(implode("\n", $e['comments'])); ?></textarea></td>
<td><button type="button" class="btn btn-sm btn-warning reset" data-index="<?= $i ?>">Сбросить</button></td>
<td><button type="button" class="btn btn-sm btn-secondary setpwd" data-index="<?= $i ?>">Задать</button></td>
<td>
<button type="button" class="btn btn-sm btn-danger delete">Удалить</button>
<input type="hidden" name="password_hash[<?= $i ?>]" value="<?= htmlspecialchars($e['hash']) ?>">
<input type="hidden" name="password_plain[<?= $i ?>]" value="">
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<button type="button" id="add" class="btn btn-primary">+</button>
<div class="mt-3">
<button type="submit" class="btn btn-success">Сохранить</button>
<button type="button" id="cancel" class="btn btn-secondary">Отмена</button>
</div>
</form>

<!-- Modal show password -->
<div class="modal" id="pwModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Новый пароль</h5></div>
      <div class="modal-body"><code id="shownPassword"></code></div>
      <div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">OK</button></div>
    </div>
  </div>
</div>

<!-- Modal set password -->
<div class="modal" id="setModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Задать пароль</h5></div>
      <div class="modal-body">
        <input type="password" id="setPasswordInput" class="form-control">
        <input type="hidden" id="setIndex">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="setPasswordSave">Сохранить</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
      </div>
</div>
</div>
</div>

<!-- Modal backups -->
<div class="modal" id="backupsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Резервные копии</h5></div>
      <div class="modal-body">
        <?php if (empty($backups)): ?>
          <p>Нет резервных копий</p>
        <?php else: ?>
          <ul class="list-group">
          <?php foreach ($backups as $b): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?= htmlspecialchars($b['time']) ?>
              <form method="post" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="restore" value="<?= htmlspecialchars($b['file']) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Восстановить</button>
              </form>
            </li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button></div>
    </div>
  </div>
</div>

<script>
var rowIndex = <?php echo count($entries); ?>;
function randomPassword(len){
    var chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    var p='';
    for(var i=0;i<len;i++){p+=chars.charAt(Math.floor(Math.random()*chars.length));}
    return p;
}
$(function(){
    $('#add').click(function(){
        var i=rowIndex++;
        var row='<tr>'+
            '<td class="text-center"><input type="checkbox" name="active['+i+']" checked></td>'+
            '<td><input type="text" class="form-control" name="username['+i+']"></td>'+
            '<td><textarea class="form-control" name="comment['+i+']" rows="2"></textarea></td>'+
            '<td><button type="button" class="btn btn-sm btn-warning reset" data-index="'+i+'">Сбросить</button></td>'+
            '<td><button type="button" class="btn btn-sm btn-secondary setpwd" data-index="'+i+'">Задать</button></td>'+
            '<td><button type="button" class="btn btn-sm btn-danger delete">Удалить</button>'+
            '<input type="hidden" name="password_hash['+i+']" value="">'+
            '<input type="hidden" name="password_plain['+i+']" value=""></td>'+
            '</tr>';
        $('#users tbody').append(row);
    });
  $('#users').on('click','.delete',function(){
      $(this).closest('tr').remove();
  });
  $('#users').on('change','input[type="checkbox"][name^="active"]',function(){
      $(this).closest('tr').toggleClass('table-secondary', !this.checked);
  });
    $('#users').on('click','.reset',function(){
        var i=$(this).data('index');
        var pass=randomPassword(12);
        $('input[name="password_plain['+i+']"]').val(pass);
        $('#shownPassword').text(pass);
        $('#pwModal').modal('show');
    });
    $('#users').on('click','.setpwd',function(){
        $('#setIndex').val($(this).data('index'));
        $('#setPasswordInput').val('');
        $('#setModal').modal('show');
    });
    $('#setPasswordSave').click(function(){
        var i=$('#setIndex').val();
        var pass=$('#setPasswordInput').val();
        $('input[name="password_plain['+i+']"]').val(pass);
        $('#setModal').modal('hide');
    });
    $('#cancel').click(function(){
        window.location.reload();
    });
    $('#showBackups').click(function(){
        $('#backupsModal').modal('show');
    });
});
</script>
</body>
</html>
