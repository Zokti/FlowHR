<!-- Модальное окно добавления теста -->
<div class="modal fade" id="addTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Добавить тест</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTestForm" action="admin_actions.php?action=add_test" method="POST">
                    <div class="mb-3">
                        <label for="testTitle" class="form-label">Название теста</label>
                        <input type="text" class="form-control" id="testTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="testTimeLimit" class="form-label">Лимит времени (сек)</label>
                        <input type="number" class="form-control" id="testTimeLimit" name="time_limit" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования теста -->
<div class="modal fade" id="editTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать тест</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editTestForm" action="admin_actions.php?action=edit_test" method="POST">
                    <input type="hidden" name="id" id="editTestId">
                    <div class="mb-3">
                        <label for="editTestTitle" class="form-label">Название теста</label>
                        <input type="text" class="form-control" id="editTestTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTestTimeLimit" class="form-label">Лимит времени (сек)</label>
                        <input type="number" class="form-control" id="editTestTimeLimit" name="time_limit" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления теста -->
<div class="modal fade" id="deleteTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить тест</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этот тест?</p>
                <form id="deleteTestForm" action="admin_actions.php?action=delete_test" method="POST">
                    <input type="hidden" name="id" id="deleteTestId">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>