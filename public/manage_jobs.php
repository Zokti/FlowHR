$query = "
    SELECT j.*, 
           e.name as experience_name,
           s.amount as salary_amount,
           u.name as hr_name
    FROM jobs j
    LEFT JOIN experience e ON j.experience_id = e.id
    LEFT JOIN salary s ON j.salary_id = s.id
    LEFT JOIN users u ON j.hr_id = u.id
    ORDER BY j.created_at DESC
";

<style>
    body {
        font-family: 'Arial', sans-serif;
        background: #f0f2f5;
        margin-left: 250px;
        padding: 30px;
        min-height: 100vh;
    }

    .jobs-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px;
        background: white;
        border-radius: 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }

    .page-title {
        color: #1a1a1a;
        font-size: 36px;
        margin-bottom: 40px;
        text-align: center;
        position: relative;
        padding-bottom: 20px;
        font-weight: 800;
    }

    .page-title:after {
        content: '';
        display: block;
        width: 100px;
        height: 5px;
        background: linear-gradient(90deg, #FF6F61, #FF8E53);
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        bottom: 0;
        border-radius: 3px;
    }

    .filters {
        display: flex;
        gap: 20px;
        margin-bottom: 40px;
        flex-wrap: wrap;
        background: #ffffff;
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .search-input {
        flex: 1;
        min-width: 250px;
        padding: 16px 25px;
        border: 2px solid #e0e0e0;
        border-radius: 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }

    .search-input:focus {
        outline: none;
        border-color: #FF6F61;
        box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
        background: #fff;
    }

    .status-select {
        padding: 16px 25px;
        border: 2px solid #e0e0e0;
        border-radius: 15px;
        font-size: 1rem;
        min-width: 180px;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f8f9fa;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23FF6F61' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 20px;
    }

    .status-select:focus {
        outline: none;
        border-color: #FF6F61;
        box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
        background: #fff;
    }

    .jobs-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .jobs-table th,
    .jobs-table td {
        padding: 20px 25px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .jobs-table th {
        background: #f8f9fa;
        color: #1a1a1a;
        font-weight: 600;
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .jobs-table tr:hover {
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .job-title {
        font-weight: 600;
        color: #1a1a1a;
        font-size: 1.1rem;
    }

    .job-description {
        color: #666;
        font-size: 0.95rem;
        line-height: 1.5;
        margin-top: 5px;
    }

    .job-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .status-active { 
        background: #e8f5e9; 
        color: #2e7d32;
    }
    .status-closed { 
        background: #ffebee; 
        color: #c62828;
    }

    .job-info {
        display: flex;
        align-items: center;
        gap: 15px;
        color: #666;
        font-size: 0.95rem;
    }

    .job-info i {
        color: #FF6F61;
    }

    .action-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: nowrap;
        justify-content: flex-start;
        align-items: center;
    }

    .btn-action {
        padding: 12px 24px;
        border: none;
        border-radius: 12px;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #FF6F61;
        color: white;
        font-weight: 600;
        white-space: nowrap;
        min-width: 140px;
        justify-content: center;
    }

    .btn-action:hover {
        background: #FF8E53;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
    }

    .btn-action:active {
        transform: translateY(0);
    }

    .btn-action i {
        font-size: 1.1rem;
        transition: transform 0.3s ease;
    }

    .btn-action:hover i {
        transform: scale(1.2);
    }

    .alert {
        padding: 20px 25px;
        border-radius: 15px;
        margin-bottom: 25px;
        animation: slideIn 0.5s ease;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .alert i {
        font-size: 1.5rem;
    }

    .alert-success {
        background: #e8f5e9;
        border: 1px solid #a5d6a7;
        color: #2e7d32;
    }

    .alert-error {
        background: #ffebee;
        border: 1px solid #ef9a9a;
        color: #c62828;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Стили для модальных окон */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .modal-overlay.active {
        opacity: 1;
    }

    .modal-window {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        background: white;
        padding: 40px;
        border-radius: 20px;
        min-width: 500px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        z-index: 1001;
    }

    .modal-overlay.active .modal-window {
        transform: translate(-50%, -50%) scale(1);
    }

    .modal-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .modal-title {
        font-size: 28px;
        color: #1a1a1a;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .modal-subtitle {
        color: #666;
        font-size: 1.1rem;
        line-height: 1.6;
        max-width: 400px;
        margin: 0 auto;
    }

    .modal-body {
        margin-bottom: 30px;
        background: #f8f9fa;
        padding: 25px;
        border-radius: 15px;
    }

    .modal-footer {
        display: flex;
        justify-content: center;
        gap: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .modal-btn {
        padding: 15px 35px;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 180px;
        justify-content: center;
    }

    .modal-btn-cancel {
        background: white;
        border: 2px solid #FF6F61;
        color: #FF6F61;
    }

    .modal-btn-cancel:hover {
        background: #FFF8E1;
        border-color: #FF8E53;
        color: #FF8E53;
    }

    .modal-btn-confirm {
        background: #FF6F61;
        border: none;
        color: white;
    }

    .modal-btn-confirm:hover {
        background: #FF8E53;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 111, 97, 0.3);
    }

    .modal-btn:hover {
        transform: translateY(-2px);
    }

    .modal-btn:active {
        transform: translateY(0);
    }

    .modal-select {
        width: 100%;
        padding: 15px 25px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        font-size: 1.1rem;
        color: #1a1a1a;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 25px;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23FF6F61' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 25px center;
        background-size: 20px;
    }

    .modal-select:hover {
        border-color: #FF6F61;
    }

    .modal-select:focus {
        outline: none;
        border-color: #FF6F61;
        box-shadow: 0 0 0 4px rgba(255, 111, 97, 0.15);
    }
</style> 