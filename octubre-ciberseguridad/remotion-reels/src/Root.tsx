import { Composition } from "remotion";
import { Reel } from "./Reel";
import { Webinar } from "./Webinar";
import { WebinarHoy } from "./WebinarHoy";

// 5 escenas de 4 s con 4 transiciones de 0,5 s: 600 - 60 = 540 fotogramas (18 s a 30 fps)
export const RemotionRoot: React.FC = () => {
  return (
    <>
      <Composition
        id="ReelSegura"
        component={Reel}
        durationInFrames={540}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          pilar: "Identidades",
          marca: "segura",
          fondo: "bg_identidades.jpg",
          pregunta: "¿Quién tiene *acceso* a sus sistemas críticos?",
          puntos: [
            "Cuentas privilegiadas en bóveda, con rotación de credenciales",
            "Acceso de terceros sin VPN y con sesiones grabadas",
            "Mínimo privilegio en endpoints y gestión de secretos DevOps",
          ],
          seed: 21,
        }}
      />
      <Composition
        id="ReelFaronics"
        component={Reel}
        durationInFrames={540}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          pilar: "Endpoints",
          marca: "faronics",
          fondo: "bg_endpoints.jpg",
          pregunta: "¿Puede *mantener y recuperar* sus equipos en un estado seguro?",
          puntos: [
            "Deep Freeze restaura el equipo a su estado base con cada reinicio",
            "Pensado para laboratorios, kioscos y salas de capacitación",
            "Deep Freeze Cloud para administrar equipos remotos",
          ],
          seed: 25,
        }}
      />
      <Composition
        id="ReelAikido"
        component={Reel}
        durationInFrames={540}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          pilar: "Aplicaciones",
          marca: "aikido",
          fondo: "bg_aplicaciones.jpg",
          pregunta: "¿Qué riesgos llegan a *producción* con su software?",
          puntos: [
            "Código, dependencias y runtime en una sola plataforma",
            "AutoTriage: prioriza según alcanzabilidad (reachability)",
            "AutoFix: propone la versión segura y abre el pull request",
          ],
          seed: 23,
        }}
      />
      <Composition
        id="ReelWebinarSafetica"
        component={Webinar}
        durationInFrames={555}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          marca: "safetica",
          fondo: "bg_aplicaciones.jpg",
          dia: "27",
          mes: "octubre",
          gancho: ["¿Tu equipo usa IA?", "Protege los datos sin frenar la productividad"],
          mensaje: ["DLP con Insider Risk", "en una sola propuesta"],
          titulo: "Detecta patrones de riesgo",
          puntos: ["Descargas masivas", "Copias", "Accesos fuera de horario"],
          horaChile: "11:00 hrs Chile",
          horaPeruColombia: "09:00 hrs Perú y Colombia",
          seed: 111,
        }}
      />
      <Composition
        id="ReelWebinarSafeticaHoy1350"
        component={WebinarHoy}
        durationInFrames={375}
        fps={30}
        width={1080}
        height={1350}
        defaultProps={{
          marca: "safetica",
          fondo: "bg_aplicaciones.jpg",
          dia: "27",
          mes: "octubre",
          gancho: ["¿Tu equipo usa IA?", "Protege los datos sin frenar la productividad"],
          frase: "Hoy te esperamos en nuestro webinar de Safetica",
          dy: 0,
          mensaje: ["", ""],
          titulo: "",
          puntos: [],
          horaChile: "11:00 hrs Chile",
          horaPeruColombia: "09:00 hrs Perú y Colombia",
          seed: 111,
        }}
      />
      <Composition
        id="ReelWebinarSafeticaHoy1920"
        component={WebinarHoy}
        durationInFrames={375}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          marca: "safetica",
          fondo: "bg_aplicaciones.jpg",
          dia: "27",
          mes: "octubre",
          gancho: ["¿Tu equipo usa IA?", "Protege los datos sin frenar la productividad"],
          frase: "Hoy te esperamos en nuestro webinar de Safetica",
          dy: 300,
          mensaje: ["", ""],
          titulo: "",
          puntos: [],
          horaChile: "11:00 hrs Chile",
          horaPeruColombia: "09:00 hrs Perú y Colombia",
          seed: 111,
        }}
      />
      <Composition
        id="ReelNovDomum"
        component={Reel}
        durationInFrames={540}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          etiqueta: "Noviembre",
          pilar: "Identidades",
          marca: "segura",
          fondo: "bgm_segura.jpg",
          pregunta: "¿Cómo acceden sus *proveedores* a los sistemas críticos?",
          puntos: [
            "Acceso desde el navegador, sin VPN, con enlace único que vence",
            "Sesión en contenedor aislado, grabada y con monitoreo en vivo",
            "Control por sistema, horario y ubicación",
          ],
          brand: { hi: "#4DE8D0", acc: "#00D1B5", acc2: "#00A892", base: "#003A32", dark: "#001F1B", tinte: "0,31,27", onacc: "#012B25" },
          seed: 31,
        }}
      />
      <Composition
        id="ReelNovAction1"
        component={Reel}
        durationInFrames={540}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          etiqueta: "Noviembre",
          pilar: "Vulnerabilidades",
          marca: "action1",
          fondo: "bgm_action1.jpg",
          pregunta: "¿Cómo parchea equipos remotos *sin VPN*?",
          puntos: [
            "Agente con conexión saliente a la nube: sin VPN ni servidor local",
            "Parchea sistema operativo y software de terceros",
            "Si el equipo estaba apagado, recibe la actualización al reconectarse",
          ],
          brand: { hi: "#7BE6FF", acc: "#00CCFF", acc2: "#00A6D1", base: "#02457A", dark: "#021E33", tinte: "2,30,51", onacc: "#021E33" },
          seed: 33,
        }}
      />
      <Composition
        id="ReelNovVicarius"
        component={Reel}
        durationInFrames={540}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          etiqueta: "Noviembre",
          pilar: "Vulnerabilidades",
          marca: "vicarius",
          fondo: "bgm_vicarius.jpg",
          pregunta: "¿Qué hace cuando *no existe un parche*?",
          puntos: [
            "Patching automatizado, también de aplicaciones de terceros",
            "Protección sin parche mientras llega la actualización",
            "Scripting para corregir configuraciones",
          ],
          brand: { hi: "#5FE3CE", acc: "#0EC0A4", acc2: "#0A9C85", base: "#182954", dark: "#0E1A38", tinte: "14,26,56", onacc: "#08211e" },
          seed: 35,
        }}
      />
      <Composition
        id="ReelNovKriptos"
        component={Reel}
        durationInFrames={540}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          etiqueta: "Noviembre",
          pilar: "Datos",
          marca: "kriptos",
          fondo: "bgm_kriptos.jpg",
          pregunta: "¿Quién puede *ver qué* en sus documentos?",
          puntos: [
            "Visibilidad de quién accede a documentos, carpetas y unidades",
            "Detecta datos sobreexpuestos y accesos de riesgo",
            "Corrige permisos a escala",
          ],
          brand: { hi: "#9AEEFA", acc: "#6fe3f5", acc2: "#3A84BD", base: "#203C71", dark: "#0a1a35", tinte: "10,26,53", onacc: "#0a1a35" },
          seed: 37,
        }}
      />
      <Composition
        id="ReelNovSophos"
        component={Reel}
        durationInFrames={540}
        fps={30}
        width={1080}
        height={1920}
        defaultProps={{
          etiqueta: "Noviembre",
          pilar: "Red y endpoint",
          marca: "sophos",
          fondo: "bgm_sophos.jpg",
          pregunta: "¿Cómo empieza un ataque de *ransomware*?",
          puntos: [
            "79% de los ataques parte de identidades comprometidas",
            "Correo malicioso (26%) y phishing (24%) suman la mitad de los casos",
            "XDR y MDR de Sophos correlacionan firewall y endpoint",
          ],
          brand: { hi: "#B5DDFF", acc: "#5CB8FF", acc2: "#2F9AEA", base: "#0070C0", dark: "#032C52", tinte: "3,44,82", onacc: "#032C52" },
          seed: 15,
        }}
      />
    </>
  );
};
