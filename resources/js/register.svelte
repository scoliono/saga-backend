<svelte:head>
    <title>Register</title>
</svelte:head>

<h1>Register</h1>
<div class="register-form">
    <form action="/register" method="POST">
        <p><label>Email:</label> <input name="email" type="text"></p>
        <p><label>First Name:</label> <input name="first_name" type="text"></p>
        <p><label>Last Name:</label> <input name="last_name" type="text"></p>
        <p><label>Password:</label> <input name="password" type="password"></p>
        <p><label>Confirm Password:</label> <input name="confirm-password" type="password"></p>
        <button type="submit">Register</button>
    </form>
</div>