<?php

// Les comptes sont créés par un administrateur : pas d'inscription publique
test('the registration routes do not exist', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});
