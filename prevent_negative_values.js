// Prevent negative values in number inputs
document.addEventListener('DOMContentLoaded', function() {
    // Select all number input fields
    const numberInputs = document.querySelectorAll('input[type="number"]');
    
    numberInputs.forEach(input => {
        // Prevent typing minus sign
        input.addEventListener('keydown', function(e) {
            if (e.key === '-' || e.key === 'Minus') {
                e.preventDefault();
            }
        });
        
        // Prevent pasting negative values
        input.addEventListener('paste', function(e) {
            setTimeout(() => {
                if (parseFloat(this.value) < 0) {
                    this.value = 0;
                }
            }, 10);
        });
        
        // Check value on input change
        input.addEventListener('input', function() {
            if (parseFloat(this.value) < 0) {
                this.value = 0;
            }
        });
        
        // Set min attribute to 0
        input.setAttribute('min', '0');
    });
});