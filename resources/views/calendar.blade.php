<!DOCTYPE html>
<html lang="en" data-theme="cupcake">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Booking Calendar</title>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #calendar {
            max-width: 1200px;
            margin: 40px auto;
        }
    </style>
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('midtrans.client_key') }}"></script>
</head>

<body>
    <dialog id="bookingModal" class="modal">
        <div class="modal-box">
            <h3 class="text-xl font-bold">PlayStation Booking </h3>
            <hr class="my-4 border-base-300">
            <div class="steps w-full my-2">
                <div class="step step-primary" data-step="1">Form</div>
                <div class="step" data-step="2">Confirm</div>
            </div>

            <!-- Step 1: Form Booking -->
            <form id="formStep">
                <input type="hidden" id="order_id" name="order_id">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend text-base">Date</legend>
                    <input type="text" id="date" name="date" class="input w-full" readonly />
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend text-base">Name</legend>
                    <input type="text" id="name" name="name" class="input w-full" required />
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend text-base">Type</legend>
                    <select class="select w-full" id="type" name="type" required>
                        <option disabled selected>Pick a type</option>
                        <option value="PS4">PS4 - Rp 30.000</option>
                        <option value="PS5">PS5 - Rp 40.000</option>
                    </select>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend text-base">Total</legend>
                    <input type="text" id="total" class="input w-full" placeholder="Rp 0" readonly />
                    <input type="hidden" id="totalHidden" name="total" />
                </fieldset>
                <div class="modal-action mt-8">
                    <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="goToConfirmation()">Checkout</button>
                </div>
            </form>

            <!-- Step 2: Konfirmasi -->
            <div id="confirmationStep" class="hidden">
                <h3 class="text-lg font-bold mt-4 mb-6">Confirmation</h3>
                <div class="grid grid-cols-1 gap-4">
                    <!-- Date -->
                    <div class="card bg-base-100 shadow-sm">
                        <div class="card-body flex justify-between items-center">
                            <p class="text-sm font-semibold">Date</p>
                            <p class="text-base" id="confirmDate"></p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="card bg-base-100 shadow-sm">
                        <div class="card-body flex justify-between items-center">
                            <p class="text-sm font-semibold">Name</p>
                            <p class="text-base" id="confirmName"></p>
                        </div>
                    </div>

                    <!-- Type -->
                    <div class="card bg-base-100 shadow-sm">
                        <div class="card-body flex justify-between items-center">
                            <p class="text-sm font-semibold">Type</p>
                            <p class="text-base" id="confirmType"></p>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="card bg-base-100 shadow-sm">
                        <div class="card-body flex justify-between items-center">
                            <p class="text-sm font-semibold">Total</p>
                            <p class="text-base font-bold text-primary" id="confirmTotal"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-action mt-8">
                    <button type="button" class="btn btn-ghost" onclick="backToForm()">Back</button>
                    <button type="submit" class="btn btn-success" onclick="payNow()">Pay Now</button>
                </div>
            </div>
        </div>
    </dialog>

    <div id='calendar'></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: '{{ route('bookings.fetch') }}',
                dateClick: function(info) {
                    // Isi tanggal yang dipilih ke dalam input
                    document.getElementById('date').value = info.dateStr;
                    // Tampilkan modal
                    document.getElementById('bookingModal').showModal();
                }
            });
            calendar.render();
        });

        document.getElementById('type').addEventListener('change', function() {
            let harga = this.value === "PS4" ? 30000 : 40000;
            let date = new Date(document.getElementById('date').value);
            if (date.getDay() === 6 || date.getDay() === 0) harga += 50000;

            let formattedPrice = "Rp " + harga.toLocaleString("id-ID");

            document.getElementById('total').value = formattedPrice;
            document.getElementById('totalHidden').value = harga;
        });


        function setActiveStep(stepIndex) {
            document.querySelectorAll('.steps .step').forEach(step => {
                step.classList.remove('step-primary');
            });
            document.querySelector(`.steps .step[data-step="${stepIndex}"]`).classList.add('step-primary');
        }

        function goToConfirmation() {
            document.getElementById('formStep').classList.add('hidden');
            document.getElementById('confirmationStep').classList.remove('hidden');
            document.getElementById('confirmDate').innerText = document.getElementById('date').value;
            document.getElementById('confirmName').innerText = document.getElementById('name').value;
            document.getElementById('confirmType').innerText = document.getElementById('type').value;
            document.getElementById('confirmTotal').innerText = document.getElementById('total').value;

            setActiveStep(2);
        }

        function backToForm() {
            document.getElementById('confirmationStep').classList.add('hidden');
            document.getElementById('formStep').classList.remove('hidden');
            setActiveStep(1);
        }

        function closeModal() {
            document.getElementById('bookingModal').close();
        }

        function payNow() {
            const formData = new FormData(document.getElementById('formStep'));
            closeModal();

            fetch("{{ route('checkout') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    if (data.snap_token) {
                        // Trigger Snap popup with the received Snap Token
                        window.snap.pay(data.snap_token, {
                            onSuccess: function(result) {
                                alert("Payment success!");
                                console.log(result);
                                closeModal();
                                window.location.reload();
                            },
                            onPending: function(result) {
                                alert("Waiting for your payment...");
                                console.log(result);
                                closeModal();
                            },
                            onError: function(result) {
                                alert("Payment failed!");
                                console.log(result);
                                closeModal();
                            },
                            onClose: function() {
                                alert('You closed the popup without finishing the payment');
                            }
                        });
                    } else {
                        alert('Failed to get Snap Token.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your payment.');
                });
        }
    </script>
</body>

</html>
