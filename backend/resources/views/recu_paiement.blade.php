<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reçu de Paiement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 150px; }
        .info { margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 8px; border: 1px solid #ddd; }
        .signature { margin-top: 50px; }
        .footer { margin-top: 50px; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reçu de Paiement</h1>
        <p>Institut Saint Jean</p>
    </div>

    <div class="info">
        <p><strong>Date:</strong> {{ now()->format('d/m/Y H:i') }}</p>
        <p><strong>Référence:</strong> PAI-{{ $paiement->id }}</p>
    </div>

    <table class="table">
        <tr>
            <th>Apprenant</th>
            <td>{{ $paiement->apprenant->nom }} {{ $paiement->apprenant->prenom }}</td>
        </tr>
        <tr>
            <th>Formation</th>
            <td>{{ $paiement->formation->libelle }}</td>
        </tr>
        <tr>
            <th>Montant</th>
            <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <th>Méthode de paiement</th>
            <td>{{ $paiement->methode }}</td>
        </tr>
        <tr>
            <th>Type de paiement</th>
            <td>{{ $paiement->type_paiement }}</td>
        </tr>
        <tr>
            <th>Caissière</th>
            <td>{{ $paiement->caissier->nom }} {{ $paiement->caissier->prenom }}</td>
        </tr>
    </table>

    <div class="signature">
        <p>Signature du responsable:</p>
        <p>_________________________</p>
    </div>

    <div class="footer">
        <p>Merci pour votre confiance</p>
        <p>Institut Saint Jean - © {{ date('Y') }}</p>
    </div>
</body>
</html>