<script>
    import * as api from './api.js';
    let res;
    function login()
    {
        res = api.post('/login', {
            username: 'asdf',
            password: 'aaaa'
        });
    }
</script>

<svelte:head>
    <title>Login</title>
</svelte:head>

<h1>Login</h1>
<div class="login-form">
    <form action="/login" method="POST">
        <p><label>Email:</label> <input name="email" type="text"></p>
        <p><label>Password:</label> <input name="password" type="password"></p>
        <button type="submit" on:click|preventDefault={login}>Login</button>
    </form>
    <p class="response">
        {#await res}
            Loading&hellip;
        {:then json}
            JSON: {json}
        {:catch error}
            ERROR: {error}
        {/await}
    </p>
</div>
