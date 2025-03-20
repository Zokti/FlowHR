<!-- Модальное окно добавления навыка -->
<div class="modal fade" id="addSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Добавить навык</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSkillForm" action="admin_actions.php?action=add_skill" method="POST">
                    <div class="mb-3">
                        <label for="skillName" class="form-label">Название навыка</label>
                        <input type="text" class="form-control" id="skillName" name="name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования навыка -->
<div class="modal fade" id="editSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать навык</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSkillForm">
                    <input type="hidden" name="id" id="editSkillId">
                    <div class="mb-3">
                        <label for="editSkillName" class="form-label">Название навыка</label>
                        <input type="text" class="form-control" id="editSkillName" name="name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления навыка -->
<div class="modal fade" id="deleteSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить навык</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этот навык?</p>
                <form id="deleteSkillForm">
                    <input type="hidden" name="id" id="deleteSkillId">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>