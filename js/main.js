
const getTeamMember = function (teamId){
    $.ajax({
        'url': './src/get-team-member.php',
        'type': 'post',
        'dataType': 'text',
        'data': {'id': teamId},
        'success': function(response){
            let result = JSON.parse(response);
            const options = [];
            let option = `<option value="none">--select--</option>`;
            options.push(option);
            for(let i = 0; i< result.length; i++){
                let option = `<option value="${result[i].id}">${result[i].name}</option>`;
                options.push(option);
            }

            $('#team-member-dropdown').html(options.join(''));

        }
    });
}

const ensureBackHomeButton = () => {
    // Back-to-home button removed per request; keep function to avoid runtime errors.
    return;
};

const initPasswordToggle = () => {
    document.addEventListener('click', (event) => {
        const toggleButton = event.target.closest('.toggle-password');
        if (!toggleButton) {
            return;
        }

        event.preventDefault();

        let targetInput = null;
        const targetId = toggleButton.getAttribute('data-target');
        if (targetId) {
            targetInput = document.getElementById(targetId);
        }

        if (!targetInput) {
            const inputGroup = toggleButton.closest('.input-group');
            if (inputGroup) {
                targetInput = inputGroup.querySelector('input[type="password"], input[type="text"]');
            }
        }

        if (!targetInput) {
            return;
        }

        const icon = toggleButton.querySelector('i');
        const isHidden = targetInput.type === 'password';
        targetInput.type = isHidden ? 'text' : 'password';
        toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');

        if (icon) {
            icon.classList.toggle('fa-eye', !isHidden);
            icon.classList.toggle('fa-eye-slash', isHidden);
        }
    });
};

const initApp = () => {
    ensureBackHomeButton();
    initPasswordToggle();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}
