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
<body class="dashboard-screen public-screen bg-gray-100 font-sans">

    <!-- Top Header -->
    <header class="w-full bg-white px-8 py-4 shadow-sm border-b mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Smart Queue Counter</h1>
        <div id="liveClock" class="text-gray-500 font-medium">---, -- --, ---- ~ --:--:-- --</div>
    </header>

    <!-- Main Container -->
    <div class="dashboard-stage">
    <main class="public-layout">

        <!-- Left Side: Now Serving Card -->
        <section class="serving-card queue-card p-8 flex flex-col justify-between">
            <div>
                <span class="badge badge-green">NOW SERVING</span>

                <div class="serving-details my-8 text-center">
                    <h2 id="nowServingNumber" class="serving-number">#0000</h2>
                    <p class="text-sm font-semibold text-gray-500 mt-2">
                        <span id="publicQueueStatus" role="status">Counter 1: Checking status...</span>
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
            <button id="getTicketButton" onclick="requestTicket()" class="btn-primary mt-6 text-lg" disabled>
                Checking queue...
            </button>
            <p id="ticketMessage" class="text-sm text-gray-500 mt-2" role="status"></p>
        </section>

        <!-- Right Side: Next in Line -->
        <section class="waiting-card queue-card p-6">
                <div class="waiting-heading flex justify-between items-center mb-6">
                    <h3 id="waitingHeading" class="text-xl font-bold text-gray-800">Next in Line</h3>
                    <span id="waitingCountBadge" class="badge badge-orange">0 WAITING</span>
                </div>

                <div id="waitingListContainer" class="space-y-3" tabindex="0" role="region" aria-labelledby="waitingHeading">
                    <p class="text-gray-400 text-sm italic">Loading waiting list...</p>
                </div>
        </section>

    </main>
    </div>

    <!-- JavaScript -->
    <script>
        let queueActive = null;
        let requestingTicket = false;
        let ticketRevision = 0;

        function renderQueueStatus(active) {
            queueActive = active;
            const button = document.getElementById('getTicketButton');
            button.disabled = active !== true || requestingTicket;
            button.innerText = requestingTicket ? 'Getting ticket...' : active === null
                ? 'Checking queue...' : active ? 'Get A Ticket' : 'Queue Paused';
            document.getElementById('publicQueueStatus').innerText = active === null
                ? 'Counter 1: Status unavailable — reconnecting...'
                : active ? 'Counter 1: Active' : 'Counter 1: Paused / Maintenance';
        }

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
            if (requestingTicket) return;
            const revision = ticketRevision;
            try {
                const res = await fetch('../api/api.php?action=get_dashboard_data', { cache: 'no-store' });
                const data = await res.json();
                if (revision !== ticketRevision) return;
                if (!res.ok || typeof data.is_active !== 'boolean') throw new Error('Queue status unavailable.');
                renderQueueStatus(data.is_active);

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
                const scrollPosition = listContainer.scrollTop;
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
                listContainer.scrollTop = scrollPosition;

            } catch (err) {
                if (revision !== ticketRevision) return;
                renderQueueStatus(null);
                console.error('Error fetching queue data:', err);
            }
        }

        async function requestTicket() {
            if (queueActive !== true || requestingTicket) return;
            requestingTicket = true;
            ticketRevision++;
            renderQueueStatus(queueActive);
            const message = document.getElementById('ticketMessage');
            message.innerText = '';
            try {
                const formData = new FormData();
                formData.append('action', 'new_ticket');
                const res = await fetch('../api/api.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (res.ok && result.status === 'success') {
                    message.innerText = 'Your ticket: #' + String(result.queue_number).padStart(4, '0');
                } else if (result.status === 'paused') {
                    renderQueueStatus(false);
                    message.innerText = result.message;
                } else {
                    throw new Error(result.message || 'Unable to get a ticket. Please try again.');
                }
            } catch (err) {
                renderQueueStatus(null);
                message.innerText = 'Unable to confirm your ticket. Please check with the counter before trying again.';
                console.error('Error creating ticket:', err);
            } finally {
                requestingTicket = false;
                renderQueueStatus(queueActive);
                fetchDashboardData();
            }
        }

        setInterval(fetchDashboardData, 2000);
        fetchDashboardData();
    </script>
</body>
</html>
