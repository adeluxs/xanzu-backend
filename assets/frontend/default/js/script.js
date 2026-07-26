const passwordField = document.querySelector("#password");
const togglePasswordButton = document.querySelector(".toggle-password");
const installmentCards = document.querySelectorAll(".installment-card");
const summaryInstallments = document.querySelector("#summaryInstallments");
const summaryInitial = document.querySelector("#summaryInitial");
const summaryFinanced = document.querySelector("#summaryFinanced");
const summaryFees = document.querySelector("#summaryFees");
const summaryPayable = document.querySelector("#summaryPayable");
const summaryTotal = document.querySelector("#summaryTotal");

if (passwordField && togglePasswordButton) {
  togglePasswordButton.addEventListener("click", () => {
    const isPassword = passwordField.type === "password";

    passwordField.type = isPassword ? "text" : "password";
    togglePasswordButton.classList.toggle("is-visible", isPassword);
    togglePasswordButton.setAttribute("aria-pressed", String(isPassword));
    togglePasswordButton.setAttribute(
      "aria-label",
      isPassword ? "Hide password" : "Show password"
    );
  });
}

if (installmentCards.length) {
  installmentCards.forEach((card) => {
    card.addEventListener("click", () => {
      installmentCards.forEach((item) => item.classList.remove("is-active"));
      card.classList.add("is-active");

      if (summaryInstallments) {
        summaryInstallments.textContent = card.dataset.installment || "";
      }

      if (summaryInitial) {
        summaryInitial.textContent = card.dataset.initial || "";
      }

      if (summaryFinanced) {
        summaryFinanced.textContent = card.dataset.financed || "";
      }

      if (summaryFees) {
        summaryFees.textContent = card.dataset.totalFees || "";
      }

      if (summaryPayable) {
        summaryPayable.textContent = card.dataset.totalPayable || "";
      }

      if (summaryTotal) {
        summaryTotal.textContent = card.dataset.totalPayable || "";
      }
    });
  });
}
