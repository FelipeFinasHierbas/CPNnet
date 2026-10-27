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
          mensaje: ["", ""],
          titulo: "",
          puntos: [],
          horaChile: "11:00 hrs Chile",
          horaPeruColombia: "09:00 hrs Perú y Colombia",
          seed: 111,
        }}
      />
    </>
  );
};
