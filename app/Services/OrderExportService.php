<?php

namespace App\Services;

use App\Models\Order;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;

class OrderExportService
{
    private const HEADERS = [
        'Facture',
        'Numéro de commande',
        'Date de commande',
        'Type de paiement',
        'Montant total',
        'État de la commande',
        'Prénom (Facturation)',
        'Nom de famille (Facturation)',
        'Société (Facturation)',
        'Adresse 1 & 2 (Facturation)',
        'Ville (Facturation)',
        'Code de l\'état (Facturation)',
        'Code postal (Facturation)',
        'Code du pays (Facturation)',
        'E-mail (Facturation)',
        'Téléphone (Facturation)',
        'Titre de la méthode de paiement',
        'Montant de la remise panier',
        'Sous-total de la commande',
        'Montant de la livraison',
        'Montant du remboursement de la commande',
    ];

    private const STATUS_LABELS = [
        'processing' => 'En cours',
        'shipped' => 'Expédiée',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        'refunded' => 'Remboursée',
        'pending' => 'En attente',
    ];

    private const PAYMENT_LABELS = [
        'stripe' => 'Carte de crédit/débit',
        'paypal' => 'PayPal',
    ];

    /**
     * Génère un fichier ODS des commandes payées pour un mois donné.
     *
     * @return string Chemin du fichier généré
     */
    public function export(int $year, int $month): string
    {
        $orders = Order::whereNotNull('paid_at')
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month)
            ->whereIn('status', ['processing', 'shipped', 'completed'])
            ->orderByDesc('paid_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Commandes');

        // En-têtes
        foreach (self::HEADERS as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        // Données
        $row = 2;
        $totalCents = 0;

        foreach ($orders as $order) {
            $paymentLabel = self::PAYMENT_LABELS[$order->payment_method] ?? $order->payment_method ?? '';
            $statusLabel = self::STATUS_LABELS[$order->status] ?? $order->status;
            $address = trim(($order->billing_address_1 ?? '') . ' ' . ($order->billing_address_2 ?? ''));

            $sheet->setCellValue([1, $row], $order->invoice_number ?? '');
            $sheet->setCellValue([2, $row], $order->number);
            $sheet->setCellValue([3, $row], $order->paid_at->format('Y-m-d H:i'));
            $sheet->setCellValue([4, $row], $paymentLabel);
            $sheet->setCellValue([5, $row], round((float) $order->total, 2));
            $sheet->setCellValue([6, $row], $statusLabel);
            $sheet->setCellValue([7, $row], $order->billing_first_name ?? '');
            $sheet->setCellValue([8, $row], $order->billing_last_name ?? '');
            $sheet->setCellValue([9, $row], ''); // Société
            $sheet->setCellValue([10, $row], $address);
            $sheet->setCellValue([11, $row], $order->billing_city ?? '');
            $sheet->setCellValue([12, $row], ''); // Code état
            $sheet->setCellValue([13, $row], $order->billing_postcode ?? '');
            $sheet->setCellValue([14, $row], $order->billing_country ?? '');
            $sheet->setCellValue([15, $row], $order->billing_email ?? '');
            $sheet->setCellValue([16, $row], $order->billing_phone ?? '');
            $sheet->setCellValue([17, $row], $paymentLabel);
            $sheet->setCellValue([18, $row], round((float) $order->discount_total, 2));
            $sheet->setCellValue([19, $row], round((float) $order->subtotal, 2));
            $sheet->setCellValue([20, $row], round((float) $order->shipping_total, 2));
            $sheet->setCellValue([21, $row], 0); // Remboursement

            $totalCents += (int) round((float) $order->total * 100);
            $row++;
        }

        // Ligne vide + TOTAL
        $row++;
        $sheet->setCellValue([4, $row], 'TOTAL');
        $sheet->setCellValue([5, $row], $totalCents / 100);

        // Écriture du fichier
        $monthStr = sprintf('%04d-%02d', $year, $month);
        $filename = "{$monthStr}-AA.ods";
        $path = storage_path("app/{$filename}");

        $writer = new Ods($spreadsheet);
        $writer->save($path);

        return $path;
    }
}
