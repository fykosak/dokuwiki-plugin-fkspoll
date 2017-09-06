jQuery(() => {
    "use strict";
    document.querySelectorAll('.poll').forEach((poll) => {
        poll.querySelector('input[type="text"]').addEventListener('input', (event) => {
            if (!event.target.value) {
                poll.querySelectorAll('input[type="radio"]').forEach((input) => {
                    input.disabled = false;
                });
            } else {
                poll.querySelectorAll('input[type="radio"]').forEach((input) => {
                    input.disabled = true;
                    input.checked = false;
                });
            }
        });
    });
});
