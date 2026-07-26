/* 
   SMART IMS
   Login JavaScript
*/

// Get the checkbox
const showPassword = document.getElementById("showPassword");

// Get the password field
const password = document.getElementById("password");

// Check if the checkbox exists
if (showPassword) {

    // When checkbox is clicked
    showPassword.addEventListener("change", function () {

        // Show password
        if (this.checked) {

            password.type = "text";

        }

        // Hide password
        else {

            password.type = "password";

        }

    });

}