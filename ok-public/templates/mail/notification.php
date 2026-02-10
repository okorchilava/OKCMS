<!DOCTYPE html>
<html lang="ka" style="height: 100%; width: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $site_title ?> Notification</title>
    <style>
        /* Google Fonts - Noto Sans Georgian */
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700&display=swap');

        /* Reset & Basics */
        body { 
            margin: 0; 
            padding: 0; 
            background-color: #f3f4f6; 
            font-family: 'Noto Sans Georgian', 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            -webkit-font-smoothing: antialiased; 
            width: 100% !important; 
            height: 100% !important; /* მნიშვნელოვანია სიმაღლისთვის */
        }
        table { border-collapse: collapse; }
        img { border: 0; display: block; outline: none; text-decoration: none; }
        
        /* Typography */
        h1 { margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: 0.5px; line-height: 1.2; }
        .tagline { font-size: 13px; color: #d1d5db; margin-top: 4px; font-weight: 400; text-transform: uppercase; letter-spacing: 0.5px; }
        h2 { margin: 0 0 15px 0; color: #111827; font-size: 20px; font-weight: 600; }
        p { font-size: 15px; color: #4b5563; line-height: 1.6; margin: 0 0 15px 0; }
        
        /* Button */
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            margin-top: 10px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }
        .btn:hover { background-color: #1d4ed8; }

        /* Mobile Responsiveness */
        @media only screen and (max-width: 620px) {
            .header-inner { width: 90% !important; } 
            .container-body { width: 94% !important; max-width: 94% !important; }
            .content-cell { padding: 25px 20px !important; }
            h1 { font-size: 18px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; height: 100%;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" style="background-color: #f3f4f6; width: 100%; height: 100%; min-height: 100vh;">
        
        <tr style="height: 1%;">
            <td align="center" valign="top" style="background-color: #1f2937; border-bottom: 4px solid #3b82f6;">
                <table border="0" cellpadding="0" cellspacing="0" width="90%" style="max-width: 1200px;" class="header-inner">
                    <tr>
                        <td align="left" style="padding: 25px 0;">
                            <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="middle" style="padding-right: 15px;">
                                        <img src="<?= $site_logo ?>" alt="Logo" width="55" height="auto" style="display: block; border-radius: 4px;">
                                    </td>
                                    <td valign="middle">
                                        <h1 style="color: #ffffff;"><?= $site_title ?></h1>
                                        <div class="tagline"><?= $site_tagline ?></div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td align="center" valign="top" style="padding: 50px 0;">
                <table border="0" cellpadding="0" cellspacing="0" width="600" class="container-body" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); width: 600px; max-width: 600px;">
                    <tr>
                        <td class="content-cell" style="padding: 40px;">
                            
                            <h2>მოგესალმებით,</h2>
                            
                            <p>
                                <?= $message ?>
                            </p>

                            <div style="margin-top: 25px; padding-top: 10px;">
                                <a href="<?= $link ?>" class="btn">დეტალების ნახვა</a>
                            </div>

                            <div style="margin-top: 30px; border-top: 1px solid #f3f4f6; padding-top: 20px;">
                                <p style="font-size: 13px; color: #9ca3af; margin: 0;">
                                    ბმული: <a href="<?= $link ?>" style="color: #2563eb; text-decoration: none; word-break: break-all;"><?= $link ?></a>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr style="height: 1%;">
            <td align="center" valign="bottom" style="background-color: #1f2937; border-top: 4px solid #3b82f6;">
                <table border="0" cellpadding="0" cellspacing="0" width="90%" style="max-width: 1200px;" class="header-inner">
                    <tr>
                        <td align="center" style="padding: 30px 0; color: #d1d5db; font-size: 13px; font-family: 'Noto Sans Georgian', sans-serif;">
                            
                            <p style="margin: 0; margin-bottom: 5px; font-weight: 600; color: #ffffff;"><?= $site_title ?></p>
                            <p style="margin: 0;">&copy; <?= $year ?> ყველა უფლება დაცულია.</p>
                            
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>

</body>
</html>