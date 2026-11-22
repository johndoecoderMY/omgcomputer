import intlTelInput from "intl-tel-input/intlTelInputWithUtils";

const initializePhoneInputs = (root = document) => {
    root.querySelectorAll("[data-phone-input]").forEach((phoneInput) => {
        if (!(phoneInput instanceof HTMLInputElement) || phoneInput.dataset.phoneInputReady) {
            return;
        }

        const phoneInputInstance = intlTelInput(phoneInput, {
            initialCountry: "my",
            separateDialCode: true,
            countrySearch: true,
        });
        if (phoneInput.value.trim()) {
            phoneInputInstance.setNumber(phoneInput.value.trim());
        }
        phoneInput.dataset.phoneInputReady = "true";

        if (phoneInput.form) {
            phoneInput.form.addEventListener("submit", () => {
                if (phoneInput.value.trim()) {
                    const internationalNumber = phoneInputInstance.getNumber();
                    if (internationalNumber) phoneInput.value = internationalNumber;
                }
            });
        }
    });
};

window.setPhoneInputValue = (phoneInput, value) => {
    if (!(phoneInput instanceof HTMLInputElement)) return;
    const phoneInputInstance = intlTelInput.getInstance(phoneInput);
    if (phoneInputInstance) {
        phoneInputInstance.setNumber(value || "");
    } else {
        phoneInput.value = value || "";
    }
};
window.getInternationalPhoneNumber = (phoneInput) => {
    if (!(phoneInput instanceof HTMLInputElement) || !phoneInput.value.trim()) return "";
    return intlTelInput.getInstance(phoneInput)?.getNumber() || phoneInput.value.trim();
};

initializePhoneInputs();
