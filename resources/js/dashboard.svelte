<svelte:head>
    <title>Dashboard | SAGA</title>
</svelte:head>

<script>
    let value = '';
    let rev;
    $: rev = value.split('').reverse().join('');
</script>

<h1>Dashboard</h1>
<input type="text" bind:value>
<p>{rev}</p>