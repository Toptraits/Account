(() => {
  // node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js
  function bufferToBase64URLString(buffer) {
    const bytes = new Uint8Array(buffer);
    let str = "";
    for (const charCode of bytes) {
      str += String.fromCharCode(charCode);
    }
    const base64String = btoa(str);
    return base64String.replace(/\+/g, "-").replace(/\//g, "_").replace(/=/g, "");
  }

  // node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js
  function base64URLStringToBuffer(base64URLString) {
    const base64 = base64URLString.replace(/-/g, "+").replace(/_/g, "/");
    const padLength = (4 - base64.length % 4) % 4;
    const padded = base64.padEnd(base64.length + padLength, "=");
    const binary = atob(padded);
    const buffer = new ArrayBuffer(binary.length);
    const bytes = new Uint8Array(buffer);
    for (let i2 = 0; i2 < binary.length; i2++) {
      bytes[i2] = binary.charCodeAt(i2);
    }
    return buffer;
  }

  // node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js
  function browserSupportsWebAuthn() {
    return _browserSupportsWebAuthnInternals.stubThis(globalThis?.PublicKeyCredential !== void 0 && typeof globalThis.PublicKeyCredential === "function");
  }
  var _browserSupportsWebAuthnInternals = {
    stubThis: (value) => value
  };

  // node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js
  function toPublicKeyCredentialDescriptor(descriptor) {
    const { id } = descriptor;
    return {
      ...descriptor,
      id: base64URLStringToBuffer(id),
      /**
       * `descriptor.transports` is an array of our `AuthenticatorTransportFuture` that includes newer
       * transports that TypeScript's DOM lib is ignorant of. Convince TS that our list of transports
       * are fine to pass to WebAuthn since browsers will recognize the new value.
       */
      transports: descriptor.transports
    };
  }

  // node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js
  function isValidDomain(hostname) {
    return (
      // Consider localhost valid as well since it's okay wrt Secure Contexts
      hostname === "localhost" || // Support punycode (ACE) or ascii labels and domains
      /^((xn--[a-z0-9-]+|[a-z0-9]+(-[a-z0-9]+)*)\.)+([a-z]{2,}|xn--[a-z0-9-]+)$/i.test(hostname)
    );
  }

  // node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js
  var WebAuthnError = class extends Error {
    constructor({ message, code, cause, name }) {
      super(message, { cause });
      Object.defineProperty(this, "code", {
        enumerable: true,
        configurable: true,
        writable: true,
        value: void 0
      });
      this.name = name ?? cause.name;
      this.code = code;
    }
  };

  // node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js
  function identifyRegistrationError({ error, options }) {
    const { publicKey } = options;
    if (!publicKey) {
      throw Error("options was missing required publicKey property");
    }
    if (error.name === "AbortError") {
      if (options.signal instanceof AbortSignal) {
        return new WebAuthnError({
          message: "Registration ceremony was sent an abort signal",
          code: "ERROR_CEREMONY_ABORTED",
          cause: error
        });
      }
    } else if (error.name === "ConstraintError") {
      if (publicKey.authenticatorSelection?.requireResidentKey === true) {
        return new WebAuthnError({
          message: "Discoverable credentials were required but no available authenticator supported it",
          code: "ERROR_AUTHENTICATOR_MISSING_DISCOVERABLE_CREDENTIAL_SUPPORT",
          cause: error
        });
      } else if (
        // @ts-ignore: `mediation` doesn't yet exist on CredentialCreationOptions but it's possible as of Sept 2024
        options.mediation === "conditional" && publicKey.authenticatorSelection?.userVerification === "required"
      ) {
        return new WebAuthnError({
          message: "User verification was required during automatic registration but it could not be performed",
          code: "ERROR_AUTO_REGISTER_USER_VERIFICATION_FAILURE",
          cause: error
        });
      } else if (publicKey.authenticatorSelection?.userVerification === "required") {
        return new WebAuthnError({
          message: "User verification was required but no available authenticator supported it",
          code: "ERROR_AUTHENTICATOR_MISSING_USER_VERIFICATION_SUPPORT",
          cause: error
        });
      }
    } else if (error.name === "InvalidStateError") {
      return new WebAuthnError({
        message: "The authenticator was previously registered",
        code: "ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED",
        cause: error
      });
    } else if (error.name === "NotAllowedError") {
      return new WebAuthnError({
        message: error.message,
        code: "ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",
        cause: error
      });
    } else if (error.name === "NotSupportedError") {
      const validPubKeyCredParams = publicKey.pubKeyCredParams.filter((param) => param.type === "public-key");
      if (validPubKeyCredParams.length === 0) {
        return new WebAuthnError({
          message: 'No entry in pubKeyCredParams was of type "public-key"',
          code: "ERROR_MALFORMED_PUBKEYCREDPARAMS",
          cause: error
        });
      }
      return new WebAuthnError({
        message: "No available authenticator supported any of the specified pubKeyCredParams algorithms",
        code: "ERROR_AUTHENTICATOR_NO_SUPPORTED_PUBKEYCREDPARAMS_ALG",
        cause: error
      });
    } else if (error.name === "SecurityError") {
      const effectiveDomain = globalThis.location.hostname;
      if (!isValidDomain(effectiveDomain)) {
        return new WebAuthnError({
          message: `${globalThis.location.hostname} is an invalid domain`,
          code: "ERROR_INVALID_DOMAIN",
          cause: error
        });
      } else if (publicKey.rp.id !== effectiveDomain) {
        return new WebAuthnError({
          message: `The RP ID "${publicKey.rp.id}" is invalid for this domain`,
          code: "ERROR_INVALID_RP_ID",
          cause: error
        });
      }
    } else if (error.name === "TypeError") {
      if (publicKey.user.id.byteLength < 1 || publicKey.user.id.byteLength > 64) {
        return new WebAuthnError({
          message: "User ID was not between 1 and 64 characters",
          code: "ERROR_INVALID_USER_ID_LENGTH",
          cause: error
        });
      }
    } else if (error.name === "UnknownError") {
      return new WebAuthnError({
        message: "The authenticator was unable to process the specified options, or could not create a new credential",
        code: "ERROR_AUTHENTICATOR_GENERAL_ERROR",
        cause: error
      });
    }
    return error;
  }

  // node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js
  var BaseWebAuthnAbortService = class {
    constructor() {
      Object.defineProperty(this, "controller", {
        enumerable: true,
        configurable: true,
        writable: true,
        value: void 0
      });
    }
    createNewAbortSignal() {
      if (this.controller) {
        const abortError = new Error("Cancelling existing WebAuthn API call for new one");
        abortError.name = "AbortError";
        this.controller.abort(abortError);
      }
      const newController = new AbortController();
      this.controller = newController;
      return newController.signal;
    }
    cancelCeremony() {
      if (this.controller) {
        const abortError = new Error("Manually cancelling existing WebAuthn API call");
        abortError.name = "AbortError";
        this.controller.abort(abortError);
        this.controller = void 0;
      }
    }
  };
  var WebAuthnAbortService = new BaseWebAuthnAbortService();

  // node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js
  var attachments = ["cross-platform", "platform"];
  function toAuthenticatorAttachment(attachment) {
    if (!attachment) {
      return;
    }
    if (attachments.indexOf(attachment) < 0) {
      return;
    }
    return attachment;
  }

  // node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js
  async function startRegistration(options) {
    if (!options.optionsJSON && options.challenge) {
      console.warn("startRegistration() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.");
      options = { optionsJSON: options };
    }
    const { optionsJSON, useAutoRegister = false } = options;
    if (!browserSupportsWebAuthn()) {
      throw new Error("WebAuthn is not supported in this browser");
    }
    const publicKey = {
      ...optionsJSON,
      challenge: base64URLStringToBuffer(optionsJSON.challenge),
      user: {
        ...optionsJSON.user,
        id: base64URLStringToBuffer(optionsJSON.user.id)
      },
      excludeCredentials: optionsJSON.excludeCredentials?.map(toPublicKeyCredentialDescriptor)
    };
    const createOptions = {};
    if (useAutoRegister) {
      createOptions.mediation = "conditional";
    }
    createOptions.publicKey = publicKey;
    createOptions.signal = WebAuthnAbortService.createNewAbortSignal();
    let credential;
    try {
      credential = await navigator.credentials.create(createOptions);
    } catch (err) {
      throw identifyRegistrationError({ error: err, options: createOptions });
    }
    if (!credential) {
      throw new Error("Registration was not completed");
    }
    const { id, rawId, response, type } = credential;
    let transports = void 0;
    if (typeof response.getTransports === "function") {
      transports = response.getTransports();
    }
    let responsePublicKeyAlgorithm = void 0;
    if (typeof response.getPublicKeyAlgorithm === "function") {
      try {
        responsePublicKeyAlgorithm = response.getPublicKeyAlgorithm();
      } catch (error) {
        warnOnBrokenImplementation("getPublicKeyAlgorithm()", error);
      }
    }
    let responsePublicKey = void 0;
    if (typeof response.getPublicKey === "function") {
      try {
        const _publicKey = response.getPublicKey();
        if (_publicKey !== null) {
          responsePublicKey = bufferToBase64URLString(_publicKey);
        }
      } catch (error) {
        warnOnBrokenImplementation("getPublicKey()", error);
      }
    }
    let responseAuthenticatorData;
    if (typeof response.getAuthenticatorData === "function") {
      try {
        responseAuthenticatorData = bufferToBase64URLString(response.getAuthenticatorData());
      } catch (error) {
        warnOnBrokenImplementation("getAuthenticatorData()", error);
      }
    }
    return {
      id,
      rawId: bufferToBase64URLString(rawId),
      response: {
        attestationObject: bufferToBase64URLString(response.attestationObject),
        clientDataJSON: bufferToBase64URLString(response.clientDataJSON),
        transports,
        publicKeyAlgorithm: responsePublicKeyAlgorithm,
        publicKey: responsePublicKey,
        authenticatorData: responseAuthenticatorData
      },
      type,
      clientExtensionResults: credential.getClientExtensionResults(),
      authenticatorAttachment: toAuthenticatorAttachment(credential.authenticatorAttachment)
    };
  }
  function warnOnBrokenImplementation(methodName, cause) {
    console.warn(`The browser extension that intercepted this WebAuthn API call incorrectly implemented ${methodName}. You should report this error to them.
`, cause);
  }

  // node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js
  function browserSupportsWebAuthnAutofill() {
    if (!browserSupportsWebAuthn()) {
      return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
    }
    const globalPublicKeyCredential = globalThis.PublicKeyCredential;
    if (globalPublicKeyCredential?.isConditionalMediationAvailable === void 0) {
      return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
    }
    return _browserSupportsWebAuthnAutofillInternals.stubThis(globalPublicKeyCredential.isConditionalMediationAvailable());
  }
  var _browserSupportsWebAuthnAutofillInternals = {
    stubThis: (value) => value
  };

  // node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js
  function identifyAuthenticationError({ error, options }) {
    const { publicKey } = options;
    if (!publicKey) {
      throw Error("options was missing required publicKey property");
    }
    if (error.name === "AbortError") {
      if (options.signal instanceof AbortSignal) {
        return new WebAuthnError({
          message: "Authentication ceremony was sent an abort signal",
          code: "ERROR_CEREMONY_ABORTED",
          cause: error
        });
      }
    } else if (error.name === "NotAllowedError") {
      return new WebAuthnError({
        message: error.message,
        code: "ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",
        cause: error
      });
    } else if (error.name === "SecurityError") {
      const effectiveDomain = globalThis.location.hostname;
      if (!isValidDomain(effectiveDomain)) {
        return new WebAuthnError({
          message: `${globalThis.location.hostname} is an invalid domain`,
          code: "ERROR_INVALID_DOMAIN",
          cause: error
        });
      } else if (publicKey.rpId !== effectiveDomain) {
        return new WebAuthnError({
          message: `The RP ID "${publicKey.rpId}" is invalid for this domain`,
          code: "ERROR_INVALID_RP_ID",
          cause: error
        });
      }
    } else if (error.name === "UnknownError") {
      return new WebAuthnError({
        message: "The authenticator was unable to process the specified options, or could not create a new assertion signature",
        code: "ERROR_AUTHENTICATOR_GENERAL_ERROR",
        cause: error
      });
    }
    return error;
  }

  // node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js
  async function startAuthentication(options) {
    if (!options.optionsJSON && options.challenge) {
      console.warn("startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.");
      options = { optionsJSON: options };
    }
    const { optionsJSON, useBrowserAutofill = false, verifyBrowserAutofillInput = true } = options;
    if (!browserSupportsWebAuthn()) {
      throw new Error("WebAuthn is not supported in this browser");
    }
    let allowCredentials;
    if (optionsJSON.allowCredentials?.length !== 0) {
      allowCredentials = optionsJSON.allowCredentials?.map(toPublicKeyCredentialDescriptor);
    }
    const publicKey = {
      ...optionsJSON,
      challenge: base64URLStringToBuffer(optionsJSON.challenge),
      allowCredentials
    };
    const getOptions = {};
    if (useBrowserAutofill) {
      if (!await browserSupportsWebAuthnAutofill()) {
        throw Error("Browser does not support WebAuthn autofill");
      }
      const eligibleInputs = document.querySelectorAll("input[autocomplete$='webauthn']");
      if (eligibleInputs.length < 1 && verifyBrowserAutofillInput) {
        throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');
      }
      getOptions.mediation = "conditional";
      publicKey.allowCredentials = [];
    }
    getOptions.publicKey = publicKey;
    getOptions.signal = WebAuthnAbortService.createNewAbortSignal();
    let credential;
    try {
      credential = await navigator.credentials.get(getOptions);
    } catch (err) {
      throw identifyAuthenticationError({ error: err, options: getOptions });
    }
    if (!credential) {
      throw new Error("Authentication was not completed");
    }
    const { id, rawId, response, type } = credential;
    let userHandle = void 0;
    if (response.userHandle) {
      userHandle = bufferToBase64URLString(response.userHandle);
    }
    return {
      id,
      rawId: bufferToBase64URLString(rawId),
      response: {
        authenticatorData: bufferToBase64URLString(response.authenticatorData),
        clientDataJSON: bufferToBase64URLString(response.clientDataJSON),
        signature: bufferToBase64URLString(response.signature),
        userHandle
      },
      type,
      clientExtensionResults: credential.getClientExtensionResults(),
      authenticatorAttachment: toAuthenticatorAttachment(credential.authenticatorAttachment)
    };
  }

  // node_modules/@laravel/passkeys/dist/passkeys-BQIvBaZd.js
  var i = class extends Error {
    constructor(t) {
      super(t), this.name = "PasskeyError";
    }
  };
  var d = class extends i {
    constructor() {
      super("Passkeys are not supported in this browser."), this.name = "NotSupportedError";
    }
  };
  var S = class extends i {
    constructor() {
      super("The passkey operation was cancelled."), this.name = "UserCancelledError";
    }
  };
  var k = class extends i {
    constructor() {
      super("This device is already registered as a passkey."), this.name = "PasskeyExistsError";
    }
  };
  var v = class extends i {
    constructor(t) {
      const s = t ?? "this domain";
      super(
        `Passkeys can't be used on ${s}. For local development, use localhost.`
      ), this.name = "InvalidDomainError";
    }
  };
  var c = (e) => {
    if (e instanceof i)
      return e;
    if (!(e instanceof Error))
      return new i("An unknown error occurred.");
    if (R(e))
      return new v(N());
    switch (e.name) {
      case "NotAllowedError":
        return new S();
      case "InvalidStateError":
        return new k();
      case "NotSupportedError":
        return new d();
      default:
        return new i(e.message);
    }
  };
  var R = (e) => A(e) === "ERROR_INVALID_DOMAIN";
  var A = (e) => "code" in e && typeof e.code == "string" ? e.code : void 0;
  var N = () => typeof globalThis.location?.hostname == "string" && globalThis.location.hostname.length > 0 ? globalThis.location.hostname : void 0;
  var n = {};
  var O = (e) => {
    n = {
      ...n,
      ...e,
      fetch: {
        ...n.fetch,
        ...e.fetch,
        headers: {
          ...n.fetch?.headers,
          ...e.fetch?.headers
        }
      }
    };
  };
  var T = () => typeof document > "u" ? null : C() || I();
  var C = () => {
    const e = document.querySelector('meta[name="csrf-token"]');
    if (!e)
      return null;
    const t = e.getAttribute("content");
    return t ? { header: "X-CSRF-TOKEN", value: t } : null;
  };
  var I = () => {
    const e = "XSRF-TOKEN=", t = document.cookie.split("; ").find((o) => o.startsWith(e));
    if (!t)
      return null;
    const s = t.slice(e.length);
    return s ? { header: "X-XSRF-TOKEN", value: decodeURIComponent(s) } : null;
  };
  var u = async (e) => {
    const t = await fetch(e, {
      method: "GET",
      headers: {
        Accept: "application/json",
        ...n.fetch?.headers
      },
      credentials: n.fetch?.credentials ?? "same-origin"
    });
    return t.ok || await m(t), t.json();
  };
  var l = async (e, t) => {
    const s = T(), o = {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...n.fetch?.headers
    };
    s && (o[s.header] = s.value);
    const r = await fetch(e, {
      method: "POST",
      headers: o,
      credentials: n.fetch?.credentials ?? "same-origin",
      body: JSON.stringify(t)
    });
    return r.ok || await m(r), r.json();
  };
  var m = async (e) => {
    let t = `Request failed with status ${e.status}`;
    try {
      const s = await e.json();
      s && typeof s == "object" && "message" in s && typeof s.message == "string" && (t = s.message);
    } catch {
    }
    throw new Error(t);
  };
  var h = (e) => (typeof e == "function" ? e() : e) ?? false;
  var a = {
    registerOptions: "/user/passkeys/options",
    registerStore: "/user/passkeys",
    verifyOptions: "/passkeys/login/options",
    verifySubmit: "/passkeys/login"
  };
  var j = {
    /**
     * Configure the passkeys client.
     */
    configure(e) {
      O(e);
    },
    /**
     * Check if the browser supports passkeys.
     */
    isSupported() {
      return browserSupportsWebAuthn();
    },
    /**
     * Check if the browser supports passkey autofill.
     */
    async isAutofillSupported() {
      return browserSupportsWebAuthnAutofill();
    },
    /**
     * Register a new passkey for the authenticated user.
     */
    async register(e) {
      if (!this.isSupported())
        throw new d();
      this.cancel();
      try {
        const t = p(e, {
          options: a.registerOptions,
          submit: a.registerStore
        }), { options: s } = await u(t.optionsRoute), o = await startRegistration({ optionsJSON: s }), r = {
          name: e.name,
          credential: o
        };
        return await l(
          t.submitRoute,
          r
        );
      } catch (t) {
        throw c(t);
      }
    },
    /**
     * Verify with a passkey.
     */
    async verify(e = {}) {
      if (!this.isSupported())
        throw new d();
      this.cancel();
      try {
        const t = p(e, {
          options: a.verifyOptions,
          submit: a.verifySubmit
        }), { options: s } = await u(
          t.optionsRoute
        ), r = {
          credential: await startAuthentication({ optionsJSON: s }),
          remember: h(e.remember)
        };
        return await l(t.submitRoute, r);
      } catch (t) {
        throw c(t);
      }
    },
    /**
     * Enable passkey autofill on the current page.
     *
     * Note that the page must have an input with `autocomplete="email webauthn"` to
     * anchor the browser's passkey picker dropdown.
     *
     * Returns the verification response on success, or `undefined` if autofill
     * is not supported or was cancelled.
     */
    async autofill(e = {}) {
      if (!(!this.isSupported() || !await this.isAutofillSupported()))
        try {
          const s = p(e, {
            options: a.verifyOptions,
            submit: a.verifySubmit
          }), { options: o } = await u(
            s.optionsRoute
          ), y = {
            credential: await startAuthentication({
              optionsJSON: o,
              useBrowserAutofill: true
            }),
            remember: h(e.remember)
          };
          return await l(s.submitRoute, y);
        } catch (s) {
          if (s instanceof Error && ["AbortError", "NotAllowedError"].includes(s.name))
            return;
          throw c(s);
        }
    },
    /**
     * Cancel any pending passkey operation.
     */
    cancel() {
      WebAuthnAbortService.cancelCeremony();
    }
  };
  var p = (e, t) => ({
    optionsRoute: e.routes?.options ?? t.options,
    submitRoute: e.routes?.submit ?? t.submit
  });

  // resources/js/passkeys-login.js
  async function configurePasskeys() {
    const response = await fetch("/passkeys/bridge-csrf", {
      credentials: "same-origin",
      headers: { Accept: "application/json" }
    });
    if (!response.ok) {
      throw new Error("Could not initialize secure passkey authentication.");
    }
    const data = await response.json();
    j.configure({
      fetch: {
        credentials: "same-origin",
        headers: {
          "X-CSRF-TOKEN": data.csrf_token
        }
      }
    });
  }
  function showStatus(message, type = "info") {
    const status = document.getElementById("loginStatus");
    if (status) {
      status.className = `status show ${type}`;
      status.textContent = message;
    }
  }
  window.schoolOsRegisterPasskey = async function() {
    if (!j.isSupported()) {
      showStatus("Passkeys are not supported by this browser or device.", "error");
      return;
    }
    if (!window.schoolOsPasskeySessionReady) {
      showStatus("Please log in again using email OTP before registering a passkey.", "error");
      return;
    }
    try {
      await configurePasskeys();
      const name = window.prompt(
        "Give this passkey a name:",
        "My device"
      );
      if (!name || !name.trim()) {
        return;
      }
      const result = await j.register({
        name: name.trim()
      });
      showStatus(`Passkey "${result.name}" registered successfully.`, "info");
      window.alert("Your passkey has been registered successfully.");
    } catch (error) {
      console.error("Passkey registration error:", error);
      showStatus(error.message || "Passkey registration failed.", "error");
    }
  };
  var loginButton = document.getElementById("passkeyLogin");
  if (loginButton) {
    loginButton.addEventListener("click", async () => {
      if (!j.isSupported()) {
        showStatus("Passkeys are not supported by this browser or device.", "error");
        return;
      }
      loginButton.disabled = true;
      showStatus("Waiting for passkey verification...");
      try {
        await configurePasskeys();
        const result = await j.verify();
        if (!result || !result.token || !result.user) {
          throw new Error("The server did not return the expected login response.");
        }
        window.dispatchEvent(
          new CustomEvent("schoolos:passkey-login", {
            detail: result
          })
        );
      } catch (error) {
        console.error("Passkey login error:", error);
        showStatus(
          error.message || "Passkey login failed. You can use email OTP.",
          "error"
        );
      } finally {
        loginButton.disabled = false;
      }
    });
  }

})();
