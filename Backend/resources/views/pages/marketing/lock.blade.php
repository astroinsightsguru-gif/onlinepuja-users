<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Access Gate — OnlinePuja Marketing OS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Cinzel:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-base: #07090e;
            --gold: #f59e0b;
            --gold-glow: rgba(245, 158, 11, 0.25);
            --border-subtle: rgba(255, 255, 255, 0.08);
        }
        body {
            background-color: var(--bg-base);
            color: #f1f5f9;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: 
                radial-gradient(circle at 50% 20%, rgba(245, 158, 11, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 50% 80%, rgba(16, 185, 129, 0.04) 0%, transparent 60%);
        }
        .cinzel { font-family: 'Cinzel', serif; }
        .lock-card {
            background: rgba(15, 20, 34, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 30px rgba(245, 158, 11, 0.1);
            text-align: center;
        }
        .btn-gold {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            transition: all 0.2s ease;
        }
        .btn-gold:hover {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            transform: translateY(-1px);
        }
        .form-control {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff;
            border-radius: 10px;
            padding: 12px 16px;
        }
        .form-control:focus {
            background: rgba(0, 0, 0, 0.6);
            border-color: var(--gold);
            color: #fff;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.25);
        }
    </style>
</head>
<body>

    <div class="lock-card">
        <div style="font-size: 42px; color: var(--gold); margin-bottom: 12px;">🕉️</div>
        <h2 class="cinzel fw-bold fs-4 text-white m-0">ONLINEPUJA<span style="color: var(--gold);">.LIVE</span></h2>
        <p class="text-muted small mb-4" style="letter-spacing: 0.5px;">Growth & AI Marketing Operating System</p>

        <div class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 mb-4 rounded-pill">
            <i class="fa-solid fa-shield-halved me-1"></i> Executive Access Only
        </div>

        @if(!empty($authError))
        <div class="alert alert-danger py-2 small mb-3 text-start" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $authError }}
        </div>
        @endif

        <form method="POST" action="/marketing">
            @csrf
            <div class="mb-3 text-start">
                <label class="form-label text-muted small fw-semibold">Administrator Password</label>
                <div class="input-group">
                    <input type="password" name="admin_password" class="form-control" placeholder="Enter admin password..." required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-gold mb-3">
                <i class="fa-solid fa-lock-open me-2"></i> Unlock Command Center
            </button>
        </form>

        <div class="pt-3 border-top border-secondary border-opacity-25 text-center">
            <a href="/admin/login" class="text-muted small text-decoration-none hover-white">
                <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Open via Admin Dashboard
            </a>
        </div>
    </div>

</body>
</html>
