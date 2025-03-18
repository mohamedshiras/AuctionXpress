// Enhanced auction action handler with better error logging
function NotifyAuctionEmail(button, auctionID, action, title, description, email) {
    // First send the email
    const status = action === 'approve' ? 'Approved' : 'Rejected';
    
    let templateParams = {
        subject: "Auction Approval Update",
        message: `Your auction "${title}" has been ${status}.`,
        email: email,
        // Add these parameters if they are expected in your template
        to_name: email.split('@')[0], // Extract name from email as fallback
        from_name: "AuctionXpress Admin"
    };

    console.log("Sending auction email with params:", templateParams);

    // Send the email with better error handling
    emailjs.send("service_da77ecq", "template_ebnasro", templateParams)
        .then(function(response) {
            console.log("Auction email sent successfully!", response.status, response.text);
            
            // Create form for database update after successful email
            submitAuctionAction(auctionID, action);
        })
        .catch(function(error) {
            console.error("Failed to send auction email:", error);
            alert("Email could not be sent, but the auction status will be updated. Error: " + error.message);
            
            // Still update the database even if email fails
            submitAuctionAction(auctionID, action);
        });
}

// Separated database update logic for auctions
function submitAuctionAction(auctionID, action) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'adminview.php';
    
    const auctionIDInput = document.createElement('input');
    auctionIDInput.type = 'hidden';
    auctionIDInput.name = 'auctionID';
    auctionIDInput.value = auctionID;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    
    form.appendChild(auctionIDInput);
    form.appendChild(actionInput);
    document.body.appendChild(form);
    
    form.submit();
}

// Enhanced user approval function with consistent pattern matching auction approval
function NotifyAccountUpdate(button, userID, action, username, email) {
    // First send the email
    const status = action === 'approve' ? 'Approved' : 'Rejected';
    
    let templateParams = {
        subject: "Account Approval Update",
        message: `Dear ${username}, your account has been ${status}.`,
        email: email,
        to_name: username,
        from_name: "AuctionXpress Admin"
    };

    console.log("Sending user approval email with params:", templateParams);

    // Send the email with better error handling
    emailjs.send("service_da77ecq", "template_ebnasro", templateParams)
        .then(function(response) {
            console.log("User approval email sent successfully!", response.status, response.text);
            
            // Create form for database update after successful email
            submitUserAction(userID, action);
        })
        .catch(function(error) {
            console.error("Failed to send user approval email:", error);
            alert("Email could not be sent, but the user status will be updated. Error: " + error.message);
            
            // Still update the database even if email fails
            submitUserAction(userID, action);
        });
}

// Separated database update logic for users
function submitUserAction(userID, action) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'adminview.php';
    
    const userIDInput = document.createElement('input');
    userIDInput.type = 'hidden';
    userIDInput.name = 'userID';
    userIDInput.value = userID;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'userAction';
    actionInput.value = action;
    
    form.appendChild(userIDInput);
    form.appendChild(actionInput);
    document.body.appendChild(form);
    
    form.submit();
}

// Function to activate different sections
function activateSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
    
    // Update active nav item
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Find and activate the clicked nav item
    event.currentTarget.classList.add('active');
}

// Initialize with first section active or search results
window.onload = function() {
    // Check URL parameters for username search
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('username')) {
        activateSection('search-users');
    } else {
        // Default to pending auctions
        activateSection('pending-auctions');
    }
};