<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Réinitialisation de mot de passe — Educaxio</title>
</head>
<body style="margin:0;padding:0;background-color:#F5F0EB;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F5F0EB;">
        <tr>
            <td align="center" style="padding:40px 16px;">

                <!-- Card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0E6E8C;padding:28px 40px;">
                            <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">Educaxio</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:36px 40px 28px;">
                            <p style="margin:0 0 20px;font-size:16px;color:#303030;line-height:1.6;">
                                Bonjour {{ $userName }},
                            </p>
                            <p style="margin:0 0 28px;font-size:15px;color:#303030;line-height:1.7;">
                                Vous recevez cet email car nous avons reçu une demande de réinitialisation du mot de passe associé à votre compte Educaxio.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;">
                                <tr>
                                    <td style="border-radius:10px;background-color:#C2510A;">
                                        <a href="{{ $resetUrl }}" target="_blank"
                                           style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;background-color:#C2510A;">
                                            Réinitialiser mon mot de passe
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 20px;font-size:13px;color:#8A8A8A;line-height:1.6;">
                                Ce lien expirera dans <strong>{{ $expireMinutes }} minutes</strong>.
                            </p>
                            <p style="margin:0 0 20px;font-size:13px;color:#8A8A8A;line-height:1.6;">
                                Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :
                            </p>
                            <p style="margin:0;font-size:12px;word-break:break-all;line-height:1.5;">
                                <a href="{{ $resetUrl }}" style="color:#0E6E8C;text-decoration:underline;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:0 40px;">
                            <hr style="border:none;border-top:1px solid #F0EDE9;margin:0;" />
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#F5F0EB;padding:20px 40px;border-radius:0 0 16px 16px;">
                            <p style="margin:0;font-size:12px;color:#8A8A8A;line-height:1.6;">
                                Si vous n'avez pas effectué cette demande, ignorez simplement cet email — votre mot de passe restera inchangé.
                            </p>
                            <p style="margin:8px 0 0;font-size:11px;color:#ABABAB;">
                                © Educaxio
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Card -->

            </td>
        </tr>
    </table>

</body>
</html>
