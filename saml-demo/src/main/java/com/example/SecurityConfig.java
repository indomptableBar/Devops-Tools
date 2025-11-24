package com.example.saml;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.saml2.provider.service.registration.RelyingPartyRegistration;
import org.springframework.security.saml2.provider.service.registration.RelyingPartyRegistrationRepository;
import org.springframework.security.saml2.provider.service.registration.InMemoryRelyingPartyRegistrationRepository;
import org.springframework.security.saml2.provider.service.registration.RelyingPartyRegistrations;
import org.springframework.security.saml2.core.Saml2X509Credential;
import org.springframework.security.web.SecurityFilterChain;

import java.io.FileInputStream;
import java.security.KeyStore;
import java.security.PrivateKey;
import java.security.cert.X509Certificate;

@Configuration
@EnableWebSecurity
public class SecurityConfig {

    @Bean
    SecurityFilterChain filterChain(HttpSecurity http) throws Exception {

        http
            .authorizeHttpRequests(auth -> auth
                .requestMatchers("/", "/error").permitAll()
                .anyRequest().authenticated()
            )
            .saml2Login()
            .and()
            .saml2Logout();

        return http.build();
    }

    @Bean
    RelyingPartyRegistrationRepository relyingPartyRegistrationRepository() throws Exception {

        RelyingPartyRegistration registration =
                RelyingPartyRegistrations
                        .fromMetadataLocation(
                                "https://adfs.example.com/FederationMetadata/2007-06/FederationMetadata.xml"
                        )
                        .registrationId("adfs")
                        .entityId("urn:myapp:saml-sp")
                        .signingX509Credentials(creds -> creds.add(getSigningCredential()))
                        .assertingPartyDetails(details -> details
                                .entityId("http://adfs.example.com/adfs/services/trust"))
                        .build();

        return new InMemoryRelyingPartyRegistrationRepository(registration);
    }

    private Saml2X509Credential getSigningCredential() throws Exception {

        KeyStore ks = KeyStore.getInstance("JKS");
        ks.load(new FileInputStream("/opt/tomcat/saml/saml-keystore.jks"),
                "changeit".toCharArray());

        PrivateKey privateKey = (PrivateKey) ks.getKey("saml-sp", "changeit".toCharArray());
        X509Certificate cert = (X509Certificate) ks.getCertificate("saml-sp");

        return new Saml2X509Credential(
                privateKey,
                cert,
                Saml2X509Credential.Saml2X509CredentialType.SIGNING,
                Saml2X509Credential.Saml2X509CredentialType.DECRYPTION
        );
    }
}