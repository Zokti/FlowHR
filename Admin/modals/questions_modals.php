<!-- Модальное окно добавления вопроса -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Добавить вопрос</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addQuestionForm" action="admin_actions.php?action=add_question" method="POST">
                    <div class="mb-3">
                        <label for="questionText" class="form-label">Текст вопроса</label>
                        <textarea class="form-control" id="questionText" name="question_text" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="testId" class="form-label">Тест</label>
                        <select class="form-select" id="testId" name="test_id" required>
                            <?php foreach ($tests as $test): ?>
                                <option value="<?= $test['id'] ?>"><?= htmlspecialchars($test['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования вопроса -->
<div class="modal fade" id="editQuestionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать вопрос</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editQuestionForm" action="admin_actions.php?action=edit_question" method="POST">
                    <input type="hidden" name="id" id="editQuestionId">
                    <div class="mb-3">
                        <label for="editQuestionText" class="form-label">Текст вопроса</label>
                        <textarea class="form-control" id="editQuestionText" name="question_text" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editQuestionTestId" class="form-label">Тест</label>
                        <select class="form-select" id="editQuestionTestId" name="test_id" required>
                            <?php foreach ($tests as $test): ?>
                                <option value="<?= $test['id'] ?>"><?= htmlspecialchars($test['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления вопроса -->
<div class="modal fade" id="deleteQuestionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить вопрос</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этот вопрос?</p>
                <form id="deleteQuestionForm" action="admin_actions.php?action=delete_question" method="POST">
                    <input type="hidden" name="id" id="deleteQuestionId">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>