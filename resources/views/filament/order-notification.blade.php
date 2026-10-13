<script>
    document.addEventListener('livewire:init', () => {
        window.FilamentRealtimeDriver.subscribe(
            'orders',
            'OrderCreated',
            (data) => {
                new FilamentNotification()
                    .title('Novo pedido recebido!')
                    .success()
                    .send();
            }
        );
    });
</script>