<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>QuVel Kit - Laravel & Quasar Hybrid</title>
        <meta name="description" content="QuVel Kit: A high-performance hybrid starter kit combining Laravel & Quasar for modern web development.">

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                background: linear-gradient(135deg, #0f172a, #1e3a8a);
                color: white;
                text-align: center;
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .container {
                max-width: 800px;
                padding: 2rem;
            }
            h1 {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1rem;
            }
            p {
                font-size: 1.25rem;
                opacity: 0.8;
                margin-bottom: 2rem;
            }
            .btn {
                display: inline-block;
                padding: 1rem 2rem;
                font-size: 1rem;
                font-weight: 600;
                background: #4f46e5;
                color: white;
                border-radius: 3px;
                text-decoration: none;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #4338ca;
            }
            footer {
                margin-top: 3rem;
                font-size: 0.875rem;
                opacity: 0.7;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Elevate Your Development with <span style="color: #4f46e5;">QuVel Kit</span></h1>
            <p>The hybrid Laravel & Quasar starter kit built for scalability, speed, and seamless deployment.</p>
            <a href="https://github.com/ItsIrv/quvel-kit/" class="btn">Get Started</a>
            <footer>Built for developers, by developers.</footer>
        </div>
    </body>
</html>
