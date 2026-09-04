<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Queue Counter - Admin Panel</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- External Custom CSS -->
    <link rel="stylesheet" href="../assets/styles/style.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    <!-- Top Header -->
    <header class="w-full bg-white px-8 py-4 shadow-sm border-b mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Smart Queue Counter <span class="text-sm font-normal text-gray-500">| Admin Dashboard</span></h1>
        <div id="liveClock" class="text-gray-500 font-medium">---, -- --, ---- ~ --:--:-- --</div>
    </header>

    <main class="max-w-6xl mx-auto space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

            <!-- Counter Controls Card -->
            <section class="md:col-span-7 queue-card p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Counter Controls</h3>
                <p class="text-sm text-gray-500 mb-6">Currently serving <span id="adminNowServing" class="font-bold text-gray-800">#0000</span> at Counter 1</p>

                <!-- Call Next Button -->
                <button onclick="callNextCustomer()" class="btn-primary text-lg flex items-center justify-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span>Call Next Customer</span>
                </button>

                <div class="grid grid-cols-2 gap-4 mt-4">
                    <button class="btn-secondary">Recall Current Customer</button>
                    <button class="btn-secondary">Pause / Maintenance</button>
                </div>

                <div class="mt-6 pt-4 border-t flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-600">Queue Status</span>
                    <span class="badge badge-green">ACCEPTING CUSTOMERS</span>
                </div>
            </section>

            <!-- Live Sensor Diagnostics Card -->
            <section class="md:col-span-5 queue-card p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-6">Live Sensor Diagnostics</h3>

                <div class="space-y-4">
                    <div class="bg-gray-100 p-4 rounded-xl flex justify-between items-center">
                        <div>
                            <p class="font-bold text-gray-800 text-sm">Push Button</p>
                            <p class="text-xs text-gray-500">Hardware input trigger</p>
                        </div>
                        <span class="badge badge-blue">WIRED</span>
                    </div>

                    <div class="bg-gray-100 p-4 rounded-xl flex justify-between items-center">
                        <div>
                            <p class="font-bold text-gray-800 text-sm">IR Presence Sensor</p>
                            <p class="text-xs text-gray-500">Customer present at desk</p>
                        </div>
                        <span id="adminIrBadge" class="badge badge-gray">Checking...</span>
                    </div>
                </div>
            </section>

        </div>

        <!-- Analytics Row -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="queue-card p-6 text-center">
                <p class="text-xs font-bold text-gray-400 tracking-wider uppercase mb-1">Total Served Today</p>
                <h4 id="totalServed" class="text-4xl font-black text-gray-800">0</h4>
            </div>

            <div class="queue-card p-6 text-center">
                <p class="text-xs font-bold text-gray-400 tracking-wider uppercase mb-1">Average Wait Time</p>
                <h4 class="text-4xl font-black text-gray-800">3m 30s</h4>
            </div>

            <div class="queue-card p-6 text-center">
                <p class="text-xs font-bold text-gray-400 tracking-wider uppercase mb-1">Current Queue Length</p>
                <h4 id="adminQueueLength" class="text-4xl font-black text-gray-800">0</h4>
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

        async function fetchAdminData() {
            try {
                const res = await fetch('../api/api.php?action=get_dashboard_data');
                const data = await res.json();

                document.getElementById('adminNowServing').innerText = data.now_serving !== "None" 
                    ? '#' + String(data.now_serving).padStart(4, '0') 
                    : '----';

                document.getElementById('adminQueueLength').innerText = data.next_in_line.length;

                const irBadge = document.getElementById('adminIrBadge');
                if (data.ir_status === 'Detected') {
                    irBadge.className = 'badge badge-green';
                    irBadge.innerText = 'DETECTED';
                } else {
                    irBadge.className = 'badge badge-gray';
                    irBadge.innerText = 'CLEAR';
                }

            } catch (err) {
                console.error('Error fetching admin data:', err);
            }
        }

        async function callNextCustomer() {
            try {
                const formData = new FormData();
                formData.append('action', 'call_next');
                const res = await fetch('../api/api.php', { method: 'POST', body: formData });
                const result = await res.json();
                
                if (result.status === 'success') {
                    fetchAdminData();
                } else {
                    alert('No waiting customers in queue.');
                }
            } catch (err) {
                console.error('Error calling next customer:', err);
            }
        }

        setInterval(fetchAdminData, 2000);
        fetchAdminData();
    </script>
</body>
</html>