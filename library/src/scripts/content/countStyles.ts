/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, colorOut, ColorValues, unit, isLightColor } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";

export const countVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("count");

    const font = themeVars("font", {
        size: 10,
    });

    const sizing = themeVars("sizing", {
        height: globalVars.fonts.size.large,
    });

    const notifications = themeVars("notifications", {
        bg: globalVars.messageColors.deleted.bg,
    });

    return {
        font,
        sizing,
        notifications,
    };
});

export const countClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = countVariables();
    const style = styleFactory("count");
    const fg = isLightColor(vars.notifications.bg)
        ? globalVars.elementaryColors.almostBlack
        : globalVars.elementaryColors.white;

    const root = style({
        ...absolutePosition.topRight(4),
        display: "block",
        backgroundColor: colorOut(vars.notifications.bg),
        height: unit(vars.sizing.height),
        lineHeight: unit(vars.sizing.height),
        minWidth: unit(vars.sizing.height),
        fontSize: unit(vars.font.size),
        fontWeight: globalVars.fonts.weights.semiBold,
        borderRadius: unit(vars.sizing.height / 2),
        whiteSpace: "nowrap",
        padding: `0 3px`,
    });
    const text = style("text", {
        display: "block",
        textAlign: "center",
        color: colorOut(fg),
    });

    return { root, text };
});
