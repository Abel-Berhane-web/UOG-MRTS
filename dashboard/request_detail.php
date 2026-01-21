<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$request_id = intval($_GET['id'] ?? 0);

$sql = "SELECT r.*, 
               u.fullname AS requester_name, u.username AS requester_username, 
               u.email AS requester_email, u.phone AS requester_phone, u.telegram AS requester_telegram,
               t.fullname AS tech_name, t.username AS tech_username, 
               t.email AS tech_email, t.phone AS tech_phone, t.telegram AS tech_telegram
        FROM requests r
        JOIN users u ON r.requested_by = u.id
        LEFT JOIN users t ON r.assigned_technician_id = t.id
        WHERE r.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Request not found.");
}

$request = $result->fetch_assoc();
$current_user = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Request Details - UoG MRTS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="request_detail.css"> 
<style>
   
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="view_requests.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Requests
            </a>
            <h1 class="page-title">Request Details <span class="request-id">#<?= $request_id ?></span></h1>
        </div>
        
        <div class="content-grid">
            <!-- Request Info Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-tools"></i> Request Information</h2>
                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $request['status'])) ?>">
                        <?= htmlspecialchars($request['status']) ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Title</span>
                    <div class="info-value"><?= htmlspecialchars($request['issue_title']) ?></div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Description</span>
                    <div class="description-box"><?= nl2br(htmlspecialchars($request['issue_description'])) ?></div>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Category</span>
                        <div class="info-value"><?= htmlspecialchars($request['category']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Campus</span>
                        <div class="info-value"><?= htmlspecialchars($request['campus']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Building</span>
                        <div class="info-value"><?= htmlspecialchars($request['building_number']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Room</span>
                        <div class="info-value"><?= htmlspecialchars($request['room_number']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Created At</span>
                        <div class="info-value"><?= date('M j, Y g:i A', strtotime($request['created_at'])) ?></div>
                    </div>
                    
                    <?php if (!empty($request['updated_at'])): ?>
                    <div class="info-item">
                        <span class="info-label">Last Updated</span>
                        <div class="info-value"><?= date('M j, Y g:i A', strtotime($request['updated_at'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Attachments Card -->
<?php if (!empty($request['image_path']) || !empty($request['audio_path']) || !empty($request['video_path'])): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-paperclip"></i> Attachments</h2>
    </div>

    <?php if (!empty($request['image_path'])): ?>
        <div class="info-item">
            <span class="info-label">Image</span>
            <div class="info-value">
                <img src="<?= htmlspecialchars($request['image_path']) ?>" 
                     alt="Uploaded Image" 
                     style="max-width: 250px; border:1px solid #ccc; border-radius:8px; margin-top:5px;">
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($request['audio_path'])): ?>
        <div class="info-item">
            <span class="info-label">Audio</span>
            <div class="info-value">
                <audio controls style="width: 100%; margin-top:5px;">
                    <source src="<?= htmlspecialchars($request['audio_path']) ?>" type="audio/mpeg">
                    <source src="<?= htmlspecialchars($request['audio_path']) ?>" type="audio/wav">
                    Your browser does not support the audio element.
                </audio>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($request['video_path'])): ?>
        <div class="info-item">
            <span class="info-label">Video</span>
            <div class="info-value">
                <video controls width="320" height="240" style="margin-top:5px;">
                    <source src="<?= htmlspecialchars($request['video_path']) ?>" type="video/mp4">
                    <source src="<?= htmlspecialchars($request['video_path']) ?>" type="video/quicktime">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

            </div>
            
            <!-- Requester Info Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user"></i> Requester Information</h2>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Name</span>
                        <div class="info-value"><?= htmlspecialchars($request['requester_name']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <div class="info-value"><?= htmlspecialchars($request['requester_username']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <div class="info-value"><?= htmlspecialchars($request['requester_email']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <div class="info-value"><?= htmlspecialchars($request['requester_phone']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Telegram</span>
                        <div class="info-value"><?= htmlspecialchars($request['requester_telegram']) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Technician Info Card -->
            <?php if (!empty($request['tech_name'])): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-cog"></i> Assigned Technician</h2>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Name</span>
                        <div class="info-value"><?= htmlspecialchars($request['tech_name']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <div class="info-value"><?= htmlspecialchars($request['tech_username']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <div class="info-value"><?= htmlspecialchars($request['tech_email']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <div class="info-value"><?= htmlspecialchars($request['tech_phone']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Telegram</span>
                        <div class="info-value"><?= htmlspecialchars($request['tech_telegram']) ?></div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-cog"></i> Technician Information</h2>
                </div>
                <p><i>No technician assigned yet.</i></p>
            </div>
            <?php endif; ?>
            
            <!-- Communication Section -->
            <?php if (!empty($request['tech_name'])): ?>
            <div class="card comms-section">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-comments"></i> Live Communication</h2>
                </div>
                
                <div class="toolbar">
                    <button id="btnStartVideo" class="btn btn-primary">
                        <i class="fas fa-video"></i> Start Video Call
                    </button>
                    <button id="btnStartVoice" class="btn btn-primary">
                        <i class="fas fa-phone"></i> Start Voice Call
                    </button>
                    <button id="btnHangup" class="btn btn-secondary" disabled>
                        <i class="fas fa-phone-slash"></i> Hang Up
                    </button>
                    <span class="call-status" id="callStatus">Idle</span>
                </div>
                
                <div class="video-area">
                    <div class="video-container">
                        <span class="video-label">My Video</span>
                        <video id="localVideo" autoplay playsinline muted></video>
                    </div>
                    <div class="video-container">
                        <span class="video-label">Technician Video</span>
                        <video id="remoteVideo" autoplay playsinline></video>
                    </div>
                </div>
                
                <div class="chat-section">
                    <h3><i class="fas fa-comment-dots"></i> Chat</h3>
                    <div id="chatBox" class="chat-box"></div>
                    <div class="chat-input-container">
                        <input type="text" id="chatInput" class="chat-input" placeholder="Type a message and press Enter…" />
                        <button id="btnSend" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Incoming call popup -->
    <div id="incomingCallPopup">
        <h3>Incoming <span id="popupCallType"></span> Call</h3>
        <p>Would you like to accept this call?</p>
        <div class="popup-buttons">
            <button id="acceptCallBtn" class="btn btn-primary">
                <i class="fas fa-phone"></i> Accept
            </button>
            <button id="rejectCallBtn" class="btn btn-secondary">
                <i class="fas fa-times"></i> Reject
            </button>
        </div>
    </div>

    <script type="module">
    // Firebase setup
    import { initializeApp } from 'https://www.gstatic.com/firebasejs/12.1.0/firebase-app.js';
    import { getDatabase, ref, push, onChildAdded, set, onValue, get } from 'https://www.gstatic.com/firebasejs/12.1.0/firebase-database.js';

    const firebaseConfig = {
        apiKey: 'AIzaSyCd4pdG964rmcFhlfrjAJr-kKbqm-thMsI',
        authDomain: 'uog-mrts.firebaseapp.com',
        databaseURL: 'https://uog-mrts-default-rtdb.firebaseio.com',
        projectId: 'uog-mrts',
        storageBucket: 'uog-mrts.appspot.com',
        messagingSenderId: '108457606873',
        appId: '1:108457606873:web:056a5443f57acf6b3408c8',
        measurementId: 'G-6KYNX81GX9'
    };

    const app = initializeApp(firebaseConfig);
    const db = getDatabase(app);

    // PHP → JS
    const requestId = '<?= $request_id ?>';
    const currentUser = '<?= $current_user ?>';

    // Chat functionality
    const chatBox = document.getElementById('chatBox');
    const chatInput = document.getElementById('chatInput');
    const btnSend = document.getElementById('btnSend');

    function escapeHTML(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function renderMsg(d){
        const mine = String(d.user) === String(currentUser);
        const msgElement = document.createElement('div');
        msgElement.className = 'msg ' + (mine ? 'me' : 'other');
        
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        
        const sender = mine ? 'Me' : (d.user === '<?= $request["assigned_technician_id"] ?>' ? 'Technician' : 'Requester');
        const time = new Date(d.time || Date.now()).toLocaleTimeString();
        
        bubble.innerHTML = `
            <div class="msg-sender">${sender}</div>
            <div>${escapeHTML(d.message || '')}</div>
            <span class="msg-time">${time}</span>
        `;
        
        msgElement.appendChild(bubble);
        chatBox.appendChild(msgElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    btnSend?.addEventListener('click', sendText);
    chatInput?.addEventListener('keydown', (e) => { 
        if (e.key === 'Enter') { 
            e.preventDefault(); 
            sendText(); 
        } 
    });

    function sendText(){
        const msg = chatInput.value.trim();
        if (!msg) return;
        
        push(ref(db, `chats/${requestId}`), {
            user: String(currentUser),
            type: 'text',
            message: msg,
            time: Date.now()
        });
        
        chatInput.value = '';
    }

    onChildAdded(ref(db, `chats/${requestId}`), (snap) => { 
        const d = snap.val(); 
        if (d) renderMsg(d); 
    });

    // WebRTC functionality (same as before)
    const btnStartVideo = document.getElementById('btnStartVideo');
    const btnStartVoice = document.getElementById('btnStartVoice');
    const btnHangup = document.getElementById('btnHangup');
    const callStatus = document.getElementById('callStatus');
    const localVideo = document.getElementById('localVideo');
    const remoteVideo = document.getElementById('remoteVideo');

    const incomingPopup = document.getElementById('incomingCallPopup');
    const popupCallType = document.getElementById('popupCallType');
    const acceptBtn = document.getElementById('acceptCallBtn');
    const rejectBtn = document.getElementById('rejectCallBtn');

    let localStream = null, peer = null, isCaller = false, currentCallType = null;
    let peerTargetUserId = <?= $request['assigned_technician_id'] ?? $request['requested_by'] ?>;
    
    const rtcConfig = { 
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'turn:numb.viagenie.ca', username: 'demo@live.com', credential: 'muazkh' }
        ]
    };

    // Determine who is the other user
    if (Number(peerTargetUserId) === Number(currentUser)) {
        peerTargetUserId = <?= $request['requested_by'] ?? $request['assigned_technician_id'] ?>;
    }

    btnStartVideo?.addEventListener('click', () => startCall('video'));
    btnStartVoice?.addEventListener('click', () => startCall('voice'));
    btnHangup?.addEventListener('click', hangUp);

    async function ensureMedia(kind) {
        if (kind === 'voice') {
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
            localVideo.srcObject = null;
        } else {
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
            localVideo.srcObject = localStream;
        }
    }

    function createPeer() {
        peer = new RTCPeerConnection(rtcConfig);
        localStream.getTracks().forEach(t => peer.addTrack(t, localStream));
        
        peer.ontrack = (ev) => { 
            remoteVideo.srcObject = ev.streams[0]; 
        };
        
        peer.onicecandidate = (ev) => {
            if (ev.candidate) {
                const bucket = isCaller ? 'callerCandidates' : 'calleeCandidates';
                push(ref(db, `calls/${requestId}/users/${peerTargetUserId}/${bucket}`), ev.candidate.toJSON());
            }
        };
    }

    // Caller functionality
    async function startCall(kind) {
        isCaller = true;
        currentCallType = kind;
        callStatus.textContent = `Starting ${kind} call…`;
        btnStartVideo.disabled = true;
        btnStartVoice.disabled = true;
        btnHangup.disabled = false;

        await ensureMedia(kind);
        createPeer();

        const offer = await peer.createOffer();
        await peer.setLocalDescription(offer);
        await set(ref(db, `calls/${requestId}/users/${peerTargetUserId}/offer`), offer);
        await set(ref(db, `calls/${requestId}/users/${peerTargetUserId}/type`), kind);

        // Listen for answer
        onValue(ref(db, `calls/${requestId}/users/${currentUser}/answer`), async (snap) => {
            const answer = snap.val();
            if (answer && !peer.currentRemoteDescription) {
                await peer.setRemoteDescription(new RTCSessionDescription(answer));
                callStatus.textContent = 'Connected';
            }
        });

        onChildAdded(ref(db, `calls/${requestId}/users/${currentUser}/calleeCandidates`), async (snap) => {
            const cand = snap.val();
            if (cand) try { 
                await peer.addIceCandidate(new RTCIceCandidate(cand)); 
            } catch (e) { 
                console.warn(e); 
            }
        });
    }

    // Incoming call functionality
    onValue(ref(db, `calls/${requestId}/users/${currentUser}/offer`), async (snap) => {
        const offer = snap.val();
        if (!offer || peer) return;

        const typeSnap = await get(ref(db, `calls/${requestId}/users/${currentUser}/type`));
        currentCallType = typeSnap.exists() ? typeSnap.val() : 'video';

        popupCallType.textContent = currentCallType;
        incomingPopup.style.display = 'block';
    });

    acceptBtn.addEventListener('click', async () => {
        incomingPopup.style.display = 'none';
        await answerCall();
    });

    rejectBtn.addEventListener('click', async () => {
        incomingPopup.style.display = 'none';
        await set(ref(db, `calls/${requestId}/users/${currentUser}`), null);
    });

    async function answerCall() {
        isCaller = false;
        callStatus.textContent = `Connected (${currentCallType})`;
        btnStartVideo.disabled = true;
        btnStartVoice.disabled = true;
        btnHangup.disabled = false;

        await ensureMedia(currentCallType);
        createPeer();

        const offer = (await get(ref(db, `calls/${requestId}/users/${currentUser}/offer`))).val();
        await peer.setRemoteDescription(new RTCSessionDescription(offer));
        
        const answer = await peer.createAnswer();
        await peer.setLocalDescription(answer);
        await set(ref(db, `calls/${requestId}/users/${peerTargetUserId}/answer`), answer);

        onChildAdded(ref(db, `calls/${requestId}/users/${currentUser}/callerCandidates`), async (snap) => {
            const cand = snap.val();
            if (cand) try { 
                await peer.addIceCandidate(new RTCIceCandidate(cand)); 
            } catch (e) { 
                console.warn(e); 
            }
        });
    }

    // Hangup functionality
    async function hangUp() {
        btnHangup.disabled = true;

        // Stop local tracks & close peer
        if (peer) { 
            peer.getSenders().forEach(s => { 
                try { s.track && s.track.stop(); } catch {} 
            }); 
            peer.close(); 
        }
        peer = null;

        // Stop local media
        if (localStream) localStream.getTracks().forEach(t => t.stop());
        localStream = null;

        localVideo.srcObject = null;
        remoteVideo.srcObject = null;

        callStatus.textContent = 'Idle';

        // Notify the other side that call ended
        if (peerTargetUserId) {
            await set(ref(db, `calls/${requestId}/users/${peerTargetUserId}/callEnded`), true);
        }

        // Clear own call data
        await set(ref(db, `calls/${requestId}/users/${currentUser}`), null);

        btnStartVideo.disabled = false;
        btnStartVoice.disabled = false;
    }

    // Listen for remote hangup
    onValue(ref(db, `calls/${requestId}/users/${currentUser}/callEnded`), snap => {
        if (snap.exists() && peer) {
            hangUp(); // automatically hangup when other side ends call
        }
    });
    </script>
</body>
</html>

<?php $conn->close(); ?>