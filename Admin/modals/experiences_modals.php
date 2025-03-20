<!-- Модальное окно добавления опыта -->
<div class="modal fade" id="addExperienceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Добавить опыт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addExperienceForm" action="admin_actions.php?action=add_experience" method="POST">
                    <div class="mb-3">
                        <label for="experienceName" class="form-label">Название опыта</label>
                        <input type="text" class="form-control" id="experienceName" name="name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования опыта -->
<div class="modal fade" id="editExperienceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать опыт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editExperienceForm" action="admin_actions.php?action=edit_experience" method="POST">
                    <input type="hidden" name="id" id="editExperienceId">
                    <div class="mb-3">
                        <label for="editExperienceName" class="form-label">Название опыта</label>
                        <input type="text" class="form-control" id="editExperienceName" name="name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления опыта -->
<div class="modal fade" id="deleteExperienceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить опыт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этот опыт?</p>
                <form id="deleteExperienceForm" action="admin_actions.php?action=delete_experience" method="POST">
                    <input type="hidden" name="id" id="deleteExperienceId">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>