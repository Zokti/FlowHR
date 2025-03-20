<!-- Модальное окно добавления зарплаты -->
<div class="modal fade" id="addSalaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Добавить зарплату</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSalaryForm" action="admin_actions.php?action=add_salary" method="POST">
                    <div class="mb-3">
                        <label for="salaryRange" class="form-label">Диапазон зарплат</label>
                        <input type="text" class="form-control" id="salaryRange" name="salary_range" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования зарплаты -->
<div class="modal fade" id="editSalaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать зарплату</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSalaryForm" action="admin_actions.php?action=edit_salary" method="POST">
                    <input type="hidden" name="id" id="editSalaryId">
                    <div class="mb-3">
                        <label for="editSalaryRange" class="form-label">Диапазон зарплат</label>
                        <input type="text" class="form-control" id="editSalaryRange" name="salary_range" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления зарплаты -->
<div class="modal fade" id="deleteSalaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить зарплату</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этот диапазон зарплат?</p>
                <form id="deleteSalaryForm" action="admin_actions.php?action=delete_salary" method="POST">
                    <input type="hidden" name="id" id="deleteSalaryId">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>