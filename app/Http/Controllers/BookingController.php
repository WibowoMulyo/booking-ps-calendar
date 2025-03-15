<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Midtrans\Snap;

class BookingController extends Controller
{
    public function fetchBookings()
    {
        // Fetch all bookings if status is paid
        $bookings = Booking::where('status', 'paid')->get();

        $events = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'title' => $booking->name . ' (' . $booking->type . ')',
                'start' => $booking->date,
                'color' => $booking->type === 'PS4' ? '#3b82f6' : '#f43f5e',
            ];
        });

        return response()->json($events);
    }

    public function checkout(Request $request)
    {
        $booking = Booking::create($request->all());

        // Konfigurasi Midtrans
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized = config('midtrans.is_sanitized');
        \Midtrans\Config::$is3ds = config('midtrans.is_3ds');

        // Buat transaksi Midtrans
        $transaction_details = [
            'order_id' => 'ORDER-' . $booking->id,
            'gross_amount' => $request->total,
        ];

        $customer_details = [
            'first_name' => $request->name,
            'email' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $request->name)) . '@gmail.com',
        ];

        $transaction = [
            'transaction_details' => $transaction_details,
            'customer_details' => $customer_details,
        ];

        try {
            // Generate Snap Token
            $snapToken = Snap::getSnapToken($transaction);

            return response()->json([
                'snap_token' => $snapToken,
                'order_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function callback(Request $request)
    {
        // Ambil data notifikasi dari Midtrans
        $payload = $request->all();

        // Verifikasi signature key (opsional, tetapi disarankan untuk keamanan)
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $serverKey);

        if ($hashed !== $payload['signature_key']) {
            return response()->json(['message' => 'Invalid signature key'], 403);
        }

        // Ambil order_id dari payload
        $orderId = $payload['order_id'];

        // Cari booking berdasarkan order_id
        $booking = Booking::where('id', str_replace('ORDER-', '', $orderId))->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // Periksa status pembayaran
        if ($payload['transaction_status'] === 'capture' || $payload['transaction_status'] === 'settlement') {
            // Jika pembayaran berhasil, update status booking menjadi 'paid'
            $booking->status = 'paid';
            $booking->save();

            return response()->json(['message' => 'Payment successful, booking status updated to paid']);
        } elseif ($payload['transaction_status'] === 'pending') {
            // Jika pembayaran masih pending
            return response()->json(['message' => 'Payment is still pending']);
        } elseif ($payload['transaction_status'] === 'deny' || $payload['transaction_status'] === 'expire' || $payload['transaction_status'] === 'cancel') {
            // Jika pembayaran gagal
            return response()->json(['message' => 'Payment failed']);
        }

        // Jika status tidak dikenali
        return response()->json(['message' => 'Unknown payment status'], 400);
    }
}
