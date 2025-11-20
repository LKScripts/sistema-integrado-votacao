function login() {
    const emailInput = document.getElementById("email");

    if (emailInput.value.includes("admin")) {
        window.location.href = "/pages/admin";
    } else {
        window.location.href = "/pages/user";
    }
}