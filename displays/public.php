<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Queue Counter - Public Display</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- External Custom CSS -->
    <link rel="stylesheet" href="../assets/styles/style.css">
</head>
<body class="p-6">

    <!-- Top Header -->
    <header class="max-w-6xl mx-auto flex justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm border">
        <h1 class="text-2xl font-bold text-gray-800">Smart Queue Counter</h1>
        <div id="liveClock" class="text-gray-500 font-medium">---, -- --, ---- ~ --:--:-- --</div>
    </header>

    <!-- Main Container -->
    <main class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-6">

        <!-- Left Side: Now Serving Card -->
        <section class="md:col-span-7 queue-card p-8 flex flex-col justify-between">
            <div>
                <span class="badge badge-green">NOW SERVING</span>

                <div class="my-8 text-center">
                    <h2 id="nowServingNumber" class="serving-number">#0000</h2>
                    <p class="text-sm font-semibold text-gray-500 mt-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 mr-1"></span>
                        Counter 1: Active
                    </p>
                </div>
            </div>

            <!-- IR Sensor Status Card -->
            <div class="bg-gray-50 p-4 rounded-xl border flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold text-gray-800">IR Sensor</p>
                        <p class="text-xs text-gray-500">Customer detected at counter</p>
                    </div>
                </div>
                <span id="irSensorBadge" class="badge badge-gray">Checking...</span>
            </div>

            <!-- Get Ticket Button -->
            <button onclick="requestTicket()" class="btn-primary mt-6 text-lg">
                Get A Ticket
            </button>
        </section>

        <!-- Right Side: Next in Line -->
        <section class="md:col-span-5 queue-card p-6 flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Next in Line</h3>
                    <span id="waitingCountBadge" class="badge badge-orange">0 WAITING</span>
                </div>

                <div id="waitingListContainer" class="space-y-3">
                    <p class="text-gray-400 text-sm italic">Loading waiting list...</p>
                </div>
            </div>
        </section>

    </main>

    <!-- JavaScript -->
    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').innerText = now.toLocaleString('en-US', {
                weekday: 'long', month: 'short', day: 'numeric', year: 'numeric',
                hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        async function fetchDashboardData() {
            try {
                const res = await fetch('../api/api.php?action=get_dashboard_data');
                const data = await res.json();

                document.getElementById('nowServingNumber').innerText = data.now_serving !== "None" 
                    ? '#' + String(data.now_serving).padStart(4, '0') 
                    : '----';

                const irBadge = document.getElementById('irSensorBadge');
                if (data.ir_status === 'Detected') {
                    irBadge.className = 'badge badge-green';
                    irBadge.innerText = 'Present';
                } else {
                    irBadge.className = 'badge badge-gray';
                    irBadge.innerText = 'Clear';
                }

                const listContainer = document.getElementById('waitingListContainer');
                const waitingBadge = document.getElementById('waitingCountBadge');
                listContainer.innerHTML = '';

                waitingBadge.innerText = `${data.next_in_line.length} WAITING`;

                if (data.next_in_line.length > 0) {
                    data.next_in_line.forEach((num, index) => {
                        const item = document.createElement('div');
                        item.className = 'bg-gray-100 p-4 rounded-xl flex justify-between items-center';
                        item.innerHTML = `
                            <span class="text-2xl font-black text-gray-800">#${String(num).padStart(4, '0')}</span>
                            <span class="text-xs text-gray-400">~ ${(index + 1) * 10}m wait</span>
                        `;
                        listContainer.appendChild(item);
                    });
                } else {
                    listContainer.innerHTML = '<p class="text-gray-400 text-sm italic">No customers currently waiting.</p>';
                }

            } catch (err) {
                console.error('Error fetching queue data:', err);
            }
        }

        async function requestTicket() {
            try {
                const formData = new FormData();
                formData.append('action', 'new_ticket');
                const res = await fetch('../api/api.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.status === 'success') {
                    fetchDashboardData();
                }
            } catch (err) {
                console.error('Error creating ticket:', err);
            }
        }

        setInterval(fetchDashboardData, 2000);
        fetchDashboardData();
    </script>
</body>
</html>