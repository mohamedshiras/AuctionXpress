// Navigation button handlers
document.getElementById('signupbutton')?.addEventListener('click', function() {
    window.location.href = 'createacc.php';
});

document.getElementById('loginbutton')?.addEventListener('click', function() {
    window.location.href = 'cwlogin.php';
});

document.getElementById('postAuctionbutton')?.addEventListener('click', function() {
    window.location.href = 'postauction.php';
});

// Logout button functionality
    document.getElementById('logoutbutton').addEventListener('click', function() {
        if (confirm('Are you sure you want to log out?')) {
            window.location.href = 'logout.php';
        }
    });

// Notification handler
document.querySelector('.notification-icon')?.addEventListener('click', function() {
    // You can replace this with actual notification functionality
    alert('Notifications clicked');
});

// Item navigation handler
function navigateToItem(auctionID) {
    window.location.href = 'itempage.php?auctionID=' + auctionID;
}

// Add this if you want to ensure the DOM is fully loaded before running scripts
document.addEventListener('DOMContentLoaded', function() {
    // You can put initialization code here
    console.log('DOM fully loaded and parsed');
});

    // Logout button functionality
    document.getElementById('logoutbutton')?.addEventListener('click', function() {
        window.location.href = 'logout.php';
    });

    // Clear form functionality
    document.querySelector('button[type="button"]').addEventListener('click', function() {
        // Clear text input for title
        document.getElementById('title').value = '';
        
        // Clear textarea for description
        document.getElementById('description').value = '';
        
        // Reset category dropdown to default
        document.getElementById('category').selectedIndex = 0;
        
        // Clear starting price
        document.getElementById('startingPrice').value = '';
        
        // Clear all file inputs
        for (let i = 0; i < 5; i++) {
            const fileInput = document.querySelector(`input[name="image${i}"]`);
            fileInput.value = '';
        }
    });

   document.getElementById('adminLoginForm').addEventListener('submit', function(event) {
            const spinner = document.querySelector('.spinner');
            const submitButton = document.querySelector('.button');
            
            submitButton.disabled = true;
            spinner.style.display = 'block';
            
            // Form will submit automatically after the spinner is shown
            setTimeout(() => {
                submitButton.disabled = false;
                spinner.style.display = 'none';
            }, 2000);
        });

document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting immediately
            document.querySelector('.spinner').style.display = 'block'; // Show the spinner
            // Simulate a delay (e.g., for an API call)
            setTimeout(() => {
                this.submit(); // Submit the form
                document.querySelector('.spinner').style.display = 'none';
            }, 1000); // Adjust the delay as needed
        });


document.getElementById('signupform').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default submission
            
            const email = document.getElementById('email').value;
            const emailPattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
            const emailError = document.getElementById('emailError');
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const passwordError = document.getElementById('passwordError');
            
            let hasError = false;

            // Check email format
            if (!emailPattern.test(email)) {
                emailError.style.display = 'block';
                hasError = true;
            } else {
                emailError.style.display = 'none';
            }

            // Check password match
            if (password !== confirmPassword) {
                passwordError.style.display = 'block';
                hasError = true;
            } else {
                passwordError.style.display = 'none';
            }

            if (hasError) {
                return;
            }

            document.querySelector('.spinner').style.display = 'block';

            // Submit the form after showing the spinner
            setTimeout(() => {
                this.submit();
                document.querySelector('.spinner').style.display = 'none';
            }, 1000);
        });

        // Hide errors when user starts typing
        document.getElementById('email').addEventListener('input', function() {
            document.getElementById('emailError').style.display = 'none';
        });

        document.getElementById('confirm-password').addEventListener('input', function() {
            document.getElementById('passwordError').style.display = 'none';
        });


        //profile pic click event
    function redirectToManageAccount() {
        window.location.href = "manageacc.php";
    }

