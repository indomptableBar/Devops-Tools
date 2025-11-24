package com.example.saml.controllers;

import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.security.saml2.provider.service.authentication.Saml2AuthenticatedPrincipal;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;

@Controller
public class HomeController {

    @GetMapping("/home")
    public String home(
            @AuthenticationPrincipal Saml2AuthenticatedPrincipal principal,
            Model model) {

        model.addAttribute("username", principal.getName());
        model.addAttribute("attributes", principal.getAttributes());

        return "home";
    }
}