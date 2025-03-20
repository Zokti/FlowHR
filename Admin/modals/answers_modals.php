<!-- Модальное окно добавления ответа -->
<div class="modal fade" id="addAnswerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Добавить ответ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAnswerForm" action="admin_actions.php?action=add_answer" method="POST">
                    <div class="mb-3">
                        <label for="answerText" class="form-label">Текст ответа</label>
                        <textarea class="form-control" id="answerText" name="answer_text" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="questionId" class="form-label">Вопрос</label>
                        <select class="form-select" id="questionId" name="question_id" required>
                            <?php foreach ($questions as $question): ?>
                                <option value="<?= $question['id'] ?>"><?= htmlspecialchars($question['question_text']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="isCorrect" class="form-label">Верный ответ</label>
                        <input type="checkbox" id="isCorrect" name="is_correct" value="1">
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования ответа -->
<div class="modal fade" id="editAnswerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Редактировать ответ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editAnswerForm" action="admin_actions.php?action=edit_answer" method="POST">
                    <input type="hidden" name="id" id="editAnswerId">
                    <div class="mb-3">
                        <label for="editAnswerText" class="form-label">Текст ответа</label>
                        <textarea class="form-control" id="editAnswerText" name="answer_text" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editAnswerQuestionId" class="form-label">Вопрос</label>
                        <select class="form-select" id="editAnswerQuestionId" name="question_id" required>
                            <?php foreach ($questions as $question): ?>
                                <option value="<?= $question['id'] ?>"><?= htmlspecialchars($question['question_text']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editAnswerIsCorrect" class="form-label">Верный ответ</label>
                        <input type="checkbox" id="editAnswerIsCorrect" name="is_correct" value="1">
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления ответа -->
<div class="modal fade" id="deleteAnswerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Удалить ответ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этот ответ?</p>
                <form id="deleteAnswerForm" action="admin_actions.php?action=delete_answer" method="POST">
                    <input type="hidden" name="id" id="deleteAnswerId">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>