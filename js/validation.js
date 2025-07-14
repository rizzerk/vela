const validation = new JustValidate("#signup");

validation
    .addField("#name", [
        { rule: "required", errorMessage: "Name is required" }
    ])
    .addField("#email", [
        { rule: "required", errorMessage: "Email is required" },
        { rule: "email", errorMessage: "Enter a valid email" },
        {
            validator: (value) => () => {
                return fetch("validate-email.php?email=" + encodeURIComponent(value))
                    .then(response => response.json())
                    .then(json => json.available);
            },
            errorMessage: "Email already taken"
        }
    ])
    .addField("#username", [
        { rule: "required", errorMessage: "Username is required" }
    ])
    .addField("#password", [
        { rule: "required", errorMessage: "Password is required" },
        { rule: "minLength", value: 8, errorMessage: "Password must be at least 8 characters" }
    ])
    .addField("#birthday", [
        { rule: "required", errorMessage: "Birthday is required" }
    ]);
