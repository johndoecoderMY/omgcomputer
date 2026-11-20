import intlTelInput from "intl-tel-input/intlTelInputWithUtils";

const phoneInput = document.getElementById("customer-phone");

if (phoneInput instanceof HTMLInputElement && phoneInput.form) {
    const phoneInputInstance = intlTelInput(phoneInput, {
        initialCountry: "my",
        separateDialCode: true,
        countrySearch: true,
    });

    phoneInput.form.addEventListener("submit", () => {
        if (phoneInput.value.trim()) {
            phoneInput.value = phoneInputInstance.getNumber();
        }
    });
}
